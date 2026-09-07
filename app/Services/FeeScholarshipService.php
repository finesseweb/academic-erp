<?php
namespace App\Services;

use App\Models\College;
use App\Models\FeeHead;
use App\Models\FeeScholarshipScheme;
use App\Models\ReservationCategory;
use App\Models\University;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FeeScholarshipService
{
    public function save(?FeeScholarshipScheme $scheme, University $university, ?College $college, array $data, int $actorId): FeeScholarshipScheme
    {
        if ($scheme && $scheme->status === 'ACTIVE') throw ValidationException::withMessages(['scheme'=>'Deactivate the scheme before editing it.']);
        $this->validateScope($university, $college, $data);
        $duplicate = FeeScholarshipScheme::query()->where('university_id',$university->id)->where('academic_session_id',$data['academic_session_id'])->where('code',strtoupper(trim($data['code'])))->when($college,fn($q)=>$q->where('college_id',$college->id),fn($q)=>$q->whereNull('college_id'))->when($scheme?->id,fn($q)=>$q->whereKeyNot($scheme->id))->exists();
        if ($duplicate) throw ValidationException::withMessages(['code'=>'This scheme code already exists for the selected owner and Academic Session.']);
        if ($data['calculation_type'] === 'PERCENTAGE' && (float)$data['benefit_value'] > 100) throw ValidationException::withMessages(['benefit_value'=>'Percentage benefit cannot exceed 100%.']);
        return DB::transaction(function () use ($scheme,$university,$college,$data,$actorId) {
            $scheme ??= new FeeScholarshipScheme();
            $scheme->fill([
                'university_id'=>$university->id,'college_id'=>$college?->id,'academic_session_id'=>$data['academic_session_id'],
                'program_template_id'=>$college ? null : ($data['program_template_id'] ?? null),
                'college_program_offering_id'=>$college ? $data['college_program_offering_id'] : null,
                'name'=>$data['name'],'code'=>strtoupper(trim($data['code'])),'benefit_type'=>$data['benefit_type'],
                'calculation_type'=>$data['calculation_type'],'benefit_value'=>$data['benefit_value'],
                'maximum_benefit_amount'=>$data['calculation_type']==='PERCENTAGE' ? ($data['maximum_benefit_amount'] ?? null) : null,
                'eligibility_mode'=>$data['eligibility_mode'],'approval_mode'=>$data['approval_mode'],'description'=>$data['description'] ?? null,
                'status'=>$scheme->exists ? $scheme->status : 'INACTIVE','created_by'=>$scheme->exists ? $scheme->created_by : $actorId,'updated_by'=>$actorId,
            ]);
            $scheme->save();
            $scheme->feeHeads()->sync($data['fee_head_ids']);
            $scheme->reservationCategories()->sync($data['eligibility_mode']==='RESERVATION_CATEGORY' ? ($data['reservation_category_ids'] ?? []) : []);
            return $scheme;
        });
    }

    public function status(FeeScholarshipScheme $scheme, University $university, ?College $college, string $status, int $actorId): void
    {
        $this->assertOwner($scheme,$university,$college);
        if ($status === 'ACTIVE') {
            if (! $scheme->feeHeads()->where('fee_heads.status','ACTIVE')->exists()) throw ValidationException::withMessages(['status'=>'At least one ACTIVE Fee Head is required before activation.']);
            if ($scheme->eligibility_mode === 'RESERVATION_CATEGORY' && ! $scheme->reservationCategories()->where('reservation_categories.status','ACTIVE')->exists()) throw ValidationException::withMessages(['status'=>'At least one ACTIVE Reservation Category is required for category-based eligibility.']);
        }
        $scheme->update(['status'=>$status,'updated_by'=>$actorId]);
    }

    public function assertOwner(FeeScholarshipScheme $scheme, University $university, ?College $college): void
    {
        abort_unless((int)$scheme->university_id===(int)$university->id && ($college ? (int)$scheme->college_id===(int)$college->id : $scheme->college_id===null),404);
    }

    private function validateScope(University $university, ?College $college, array $data): void
    {
        if ($college) {
            $offering = DB::table('college_program_offerings as cpo')
                ->join('curricula as c','c.id','=','cpo.curriculum_id')
                ->where('cpo.id',$data['college_program_offering_id'])
                ->where('cpo.college_id',$college->id)
                ->where('cpo.academic_session_id',$data['academic_session_id'])
                ->where('cpo.status','ACTIVE')
                ->where('c.lifecycle_status','ACTIVE')
                ->where('c.approval_status','APPROVED')
                ->whereNotExists(function($q){
                    $q->select(DB::raw(1))->from('curricula as successor')
                        ->whereColumn('successor.parent_curriculum_id','c.id')
                        ->where('successor.approval_status','APPROVED');
                })
                ->first();
            if (! $offering) throw ValidationException::withMessages(['college_program_offering_id'=>'Select an ACTIVE Program Offering with a current approved Curriculum for this College and Academic Session.']);
        } else {
            $sessionOk = DB::table('academic_sessions as s')
                ->where('s.id',$data['academic_session_id'])
                ->where('s.university_id',$university->id)
                ->whereExists(function($q) use($university){
                    $q->select(DB::raw(1))->from('curricula as c')
                        ->whereColumn('c.academic_session_id','s.id')
                        ->where('c.university_id',$university->id)
                        ->where('c.lifecycle_status','ACTIVE')
                        ->where('c.approval_status','APPROVED')
                        ->whereNotExists(function($q2){
                            $q2->select(DB::raw(1))->from('curricula as successor')
                                ->whereColumn('successor.parent_curriculum_id','c.id')
                                ->where('successor.approval_status','APPROVED');
                        });
                })
                ->exists();
            if (! $sessionOk) throw ValidationException::withMessages(['academic_session_id'=>'Select an Academic Session that has a current ACTIVE and APPROVED Curriculum.']);

            if (! empty($data['program_template_id'])) {
                $programOk = DB::table('program_templates as pt')
                    ->where('pt.id',$data['program_template_id'])
                    ->where('pt.university_id',$university->id)
                    ->whereExists(function($q) use($data){
                        $q->select(DB::raw(1))->from('curricula as c')
                            ->whereColumn('c.program_template_id','pt.id')
                            ->where('c.academic_session_id',$data['academic_session_id'])
                            ->where('c.lifecycle_status','ACTIVE')
                            ->where('c.approval_status','APPROVED')
                            ->whereNotExists(function($q2){
                                $q2->select(DB::raw(1))->from('curricula as successor')
                                    ->whereColumn('successor.parent_curriculum_id','c.id')
                                    ->where('successor.approval_status','APPROVED');
                            });
                    })->exists();
                if (! $programOk) throw ValidationException::withMessages(['program_template_id'=>'Selected Program does not have a current ACTIVE and APPROVED Curriculum in this Academic Session.']);
            }
        }
        $headCount = FeeHead::whereIn('id',$data['fee_head_ids'])->where('university_id',$university->id)->where(function($q) use($college){ $q->whereNull('college_id'); if($college)$q->orWhere('college_id',$college->id); })->count();
        if ($headCount !== count(array_unique($data['fee_head_ids']))) throw ValidationException::withMessages(['fee_head_ids'=>'One or more Fee Heads are outside the allowed fee domain.']);
        if (($data['eligibility_mode'] ?? '')==='RESERVATION_CATEGORY') {
            $ids = array_unique($data['reservation_category_ids'] ?? []);
            if (!$ids) throw ValidationException::withMessages(['reservation_category_ids'=>'Select at least one Reservation Category.']);
            if (ReservationCategory::whereIn('id',$ids)->where('university_id',$university->id)->count() !== count($ids)) throw ValidationException::withMessages(['reservation_category_ids'=>'Invalid Reservation Category selection.']);
        }
    }
}
