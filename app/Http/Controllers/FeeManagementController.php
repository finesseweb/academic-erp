<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\AcademicCalendarTermPeriod;
use App\Models\College;
use App\Models\CollegeProgramOffering;
use App\Models\Curriculum;
use App\Models\FeeCategory;
use App\Models\FeeHead;
use App\Models\FeeStructure;
use App\Models\FeeStructureItem;
use App\Models\ProgramTemplate;
use App\Models\University;
use App\Services\FeeFoundationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class FeeManagementController extends Controller
{
    public function universityIndex(Request $request): Response
    {
        abort_unless(
            $request->user()->hasPermission('fee_category.view') ||
            $request->user()->hasPermission('fee_head.view') ||
            $request->user()->hasPermission('fee_structure.view'),
            403
        );
        $university = University::firstOrFail();

        return Inertia::render('fee-management/university', [
            'university' => $university->only(['id','name','code']),
            'categories' => $this->categories($university->id, null),
            'heads' => $this->heads($university->id, null),
            'structures' => $this->structures($university->id, null),
            'sessions' => AcademicSession::where('university_id', $university->id)->whereIn('status', ['PLANNED','ACTIVE'])->orderByDesc('starts_on')->get(['id','name','code','status']),
            'programs' => ProgramTemplate::where('university_id', $university->id)->where('status', 'ACTIVE')->orderBy('name')->get(['id','name','code','term_structure','duration_terms']),
            'curricula' => Curriculum::with('terms:id,curriculum_id,sequence_no,name,status')
                ->where('university_id', $university->id)
                ->currentApproved()
                ->orderBy('name')
                ->orderByDesc('version')
                ->get(['id','program_template_id','academic_session_id','name','code','version']),
            'can' => [
                'categoryCreate'=>$request->user()->hasPermission('fee_category.create'),'categoryUpdate'=>$request->user()->hasPermission('fee_category.update'),'categoryEnable'=>$request->user()->hasPermission('fee_category.enable'),'categoryDisable'=>$request->user()->hasPermission('fee_category.disable'),
                'headCreate'=>$request->user()->hasPermission('fee_head.create'),'headUpdate'=>$request->user()->hasPermission('fee_head.update'),'headEnable'=>$request->user()->hasPermission('fee_head.enable'),'headDisable'=>$request->user()->hasPermission('fee_head.disable'),
                'structureCreate'=>$request->user()->hasPermission('fee_structure.create'),'structureUpdate'=>$request->user()->hasPermission('fee_structure.update'),'structureEnable'=>$request->user()->hasPermission('fee_structure.enable'),'structureDisable'=>$request->user()->hasPermission('fee_structure.disable'),'structureAdopt'=>false,
            ],
        ]);
    }

    public function collegeIndex(Request $request, College $college): Response
    {
        abort_unless(
            $request->user()->hasCollegePermission('college_fee_category.view', $college->id) ||
            $request->user()->hasCollegePermission('college_fee_head.view', $college->id) ||
            $request->user()->hasCollegePermission('college_fee_structure.view', $college->id),
            403
        );
        $university = $college->university;
        $offerings = CollegeProgramOffering::with(['programTemplate:id,name,code,term_structure,duration_terms','academicSession:id,name,code,status','curriculum.terms:id,curriculum_id,sequence_no,name,status'])
            ->where('college_id', $college->id)->orderByDesc('academic_session_id')->get()
            ->map(fn ($o) => ['id'=>$o->id,'status'=>$o->status,'program_template'=>$o->programTemplate,'academic_session'=>$o->academicSession,'curriculum_terms'=>$o->curriculum?->terms?->where('status','ACTIVE')->values()->map(fn($t)=>['sequence_no'=>$t->sequence_no,'name'=>$t->name,'status'=>$t->status])->all() ?? []]);

        return Inertia::render('fee-management/college', [
            'college' => $college->only(['id','name','code','status']),
            'categories' => $this->categories($university->id, $college->id),
            'heads' => $this->heads($university->id, $college->id),
            'structures' => $this->structures($university->id, $college->id),
            'universityStructures' => $this->universityStructuresForCollege($university->id, $college),
            'offerings' => $offerings,
            'can' => [
                'categoryCreate'=>$request->user()->hasCollegePermission('college_fee_category.create',$college->id),'categoryUpdate'=>$request->user()->hasCollegePermission('college_fee_category.update',$college->id),'categoryEnable'=>$request->user()->hasCollegePermission('college_fee_category.enable',$college->id),'categoryDisable'=>$request->user()->hasCollegePermission('college_fee_category.disable',$college->id),
                'headCreate'=>$request->user()->hasCollegePermission('college_fee_head.create',$college->id),'headUpdate'=>$request->user()->hasCollegePermission('college_fee_head.update',$college->id),'headEnable'=>$request->user()->hasCollegePermission('college_fee_head.enable',$college->id),'headDisable'=>$request->user()->hasCollegePermission('college_fee_head.disable',$college->id),
                'structureCreate'=>$request->user()->hasCollegePermission('college_fee_structure.create',$college->id),'structureUpdate'=>$request->user()->hasCollegePermission('college_fee_structure.update',$college->id),'structureEnable'=>$request->user()->hasCollegePermission('college_fee_structure.enable',$college->id),'structureDisable'=>$request->user()->hasCollegePermission('college_fee_structure.disable',$college->id),'structureAdopt'=>$request->user()->hasCollegePermission('college_fee_structure.adopt',$college->id),
            ],
        ]);
    }

    public function storeUniversityCategory(Request $r, FeeFoundationService $s): RedirectResponse { $this->u($r,'fee_category.create'); $u=University::firstOrFail(); $s->createCategory($u,null,$r->validate($this->categoryRules()),$r->user()->id,$r->ip()); return back()->with('toast',['type'=>'success','message'=>'University Fee Category created as INACTIVE.']); }
    public function updateUniversityCategory(Request $r, FeeCategory $category, FeeFoundationService $s): RedirectResponse { $this->u($r,'fee_category.update'); $u=University::firstOrFail(); $s->updateCategory($category,$u,null,$r->validate($this->categoryRules()),$r->user()->id,$r->ip()); return back()->with('toast',['type'=>'success','message'=>'University Fee Category updated.']); }
    public function statusUniversityCategory(Request $r, FeeCategory $category, FeeFoundationService $s): RedirectResponse { $status=$r->validate(['status'=>['required',Rule::in(['ACTIVE','INACTIVE'])]])['status']; $this->u($r,$status==='ACTIVE'?'fee_category.enable':'fee_category.disable'); $s->changeCategoryStatus($category,University::firstOrFail(),null,$status,$r->user()->id,$r->ip()); return back()->with('toast',['type'=>'success','message'=>'University Fee Category status updated.']); }

    public function storeUniversityHead(Request $r, FeeFoundationService $s): RedirectResponse { $this->u($r,'fee_head.create'); $u=University::firstOrFail(); $s->createHead($u,null,$r->validate($this->headRules()),$r->user()->id,$r->ip()); return back()->with('toast',['type'=>'success','message'=>'University Fee Head created as INACTIVE.']); }
    public function updateUniversityHead(Request $r, FeeHead $head, FeeFoundationService $s): RedirectResponse { $this->u($r,'fee_head.update'); $u=University::firstOrFail(); $s->updateHead($head,$u,null,$r->validate($this->headRules()),$r->user()->id,$r->ip()); return back()->with('toast',['type'=>'success','message'=>'University Fee Head updated.']); }
    public function statusUniversityHead(Request $r, FeeHead $head, FeeFoundationService $s): RedirectResponse { $status=$r->validate(['status'=>['required',Rule::in(['ACTIVE','INACTIVE'])]])['status']; $this->u($r,$status==='ACTIVE'?'fee_head.enable':'fee_head.disable'); $s->changeHeadStatus($head,University::firstOrFail(),null,$status,$r->user()->id,$r->ip()); return back()->with('toast',['type'=>'success','message'=>'University Fee Head status updated.']); }
    public function storeUniversityStructure(Request $r, FeeFoundationService $s): RedirectResponse { $this->u($r,'fee_structure.create'); $s->createStructure(University::firstOrFail(),null,$r->validate($this->structureRules(false)),$r->user()->id,$r->ip()); return back()->with('toast',['type'=>'success','message'=>'University Fee Structure created as INACTIVE.']); }
    public function updateUniversityStructure(Request $r, FeeStructure $structure, FeeFoundationService $s): RedirectResponse { $this->u($r,'fee_structure.update'); $s->updateStructure($structure,University::firstOrFail(),null,$r->validate($this->structureRules(false)),$r->user()->id,$r->ip()); return back()->with('toast',['type'=>'success','message'=>'University Fee Structure updated.']); }
    public function statusUniversityStructure(Request $r, FeeStructure $structure, FeeFoundationService $s): RedirectResponse { $status=$r->validate(['status'=>['required',Rule::in(['ACTIVE','INACTIVE'])]])['status']; $this->u($r,$status==='ACTIVE'?'fee_structure.enable':'fee_structure.disable'); $s->changeStructureStatus($structure,University::firstOrFail(),null,$status,$r->user()->id,$r->ip()); return back()->with('toast',['type'=>'success','message'=>'University Fee Structure status updated.']); }
    public function storeUniversityItem(Request $r, FeeStructure $structure, FeeFoundationService $s): RedirectResponse { $this->u($r,'fee_structure.update'); $s->upsertItem($structure,null,University::firstOrFail(),null,$r->validate($this->itemRules()),$r->user()->id,$r->ip()); return back()->with('toast',['type'=>'success','message'=>'Fee Item added.']); }
    public function updateUniversityItem(Request $r, FeeStructure $structure, FeeStructureItem $item, FeeFoundationService $s): RedirectResponse { $this->u($r,'fee_structure.update'); $s->upsertItem($structure,$item,University::firstOrFail(),null,$r->validate($this->itemRules()),$r->user()->id,$r->ip()); return back()->with('toast',['type'=>'success','message'=>'Fee Item updated.']); }
    public function destroyUniversityItem(Request $r, FeeStructure $structure, FeeStructureItem $item, FeeFoundationService $s): RedirectResponse { $this->u($r,'fee_structure.update'); $data=$r->validate(['period_no'=>['nullable','integer','min:1']]); $s->removeItemFromPeriod($structure,$item,University::firstOrFail(),null,isset($data['period_no'])?(int)$data['period_no']:null,$r->user()->id,$r->ip()); return back()->with('toast',['type'=>'success','message'=>'Fee Item removed from the billing period.']); }

    public function storeCollegeCategory(Request $r, College $college, FeeFoundationService $s): RedirectResponse { $this->c($r,$college,'college_fee_category.create'); $s->createCategory($college->university,$college,$r->validate($this->categoryRules()),$r->user()->id,$r->ip()); return back()->with('toast',['type'=>'success','message'=>'College Fee Category created as INACTIVE.']); }
    public function updateCollegeCategory(Request $r, College $college, FeeCategory $category, FeeFoundationService $s): RedirectResponse { $this->c($r,$college,'college_fee_category.update'); $s->updateCategory($category,$college->university,$college,$r->validate($this->categoryRules()),$r->user()->id,$r->ip()); return back()->with('toast',['type'=>'success','message'=>'College Fee Category updated.']); }
    public function statusCollegeCategory(Request $r, College $college, FeeCategory $category, FeeFoundationService $s): RedirectResponse { $status=$r->validate(['status'=>['required',Rule::in(['ACTIVE','INACTIVE'])]])['status']; $this->c($r,$college,$status==='ACTIVE'?'college_fee_category.enable':'college_fee_category.disable'); $s->changeCategoryStatus($category,$college->university,$college,$status,$r->user()->id,$r->ip()); return back()->with('toast',['type'=>'success','message'=>'College Fee Category status updated.']); }

    public function storeCollegeHead(Request $r, College $college, FeeFoundationService $s): RedirectResponse { $this->c($r,$college,'college_fee_head.create'); $s->createHead($college->university,$college,$r->validate($this->headRules()),$r->user()->id,$r->ip()); return back()->with('toast',['type'=>'success','message'=>'College Fee Head created as INACTIVE.']); }
    public function updateCollegeHead(Request $r, College $college, FeeHead $head, FeeFoundationService $s): RedirectResponse { $this->c($r,$college,'college_fee_head.update'); $s->updateHead($head,$college->university,$college,$r->validate($this->headRules()),$r->user()->id,$r->ip()); return back()->with('toast',['type'=>'success','message'=>'College Fee Head updated.']); }
    public function statusCollegeHead(Request $r, College $college, FeeHead $head, FeeFoundationService $s): RedirectResponse { $status=$r->validate(['status'=>['required',Rule::in(['ACTIVE','INACTIVE'])]])['status']; $this->c($r,$college,$status==='ACTIVE'?'college_fee_head.enable':'college_fee_head.disable'); $s->changeHeadStatus($head,$college->university,$college,$status,$r->user()->id,$r->ip()); return back()->with('toast',['type'=>'success','message'=>'College Fee Head status updated.']); }
    public function storeCollegeStructure(Request $r, College $college, FeeFoundationService $s): RedirectResponse { $this->c($r,$college,'college_fee_structure.create'); $s->createStructure($college->university,$college,$r->validate($this->structureRules(true)),$r->user()->id,$r->ip()); return back()->with('toast',['type'=>'success','message'=>'College Fee Structure created as INACTIVE.']); }
    public function updateCollegeStructure(Request $r, College $college, FeeStructure $structure, FeeFoundationService $s): RedirectResponse { $this->c($r,$college,'college_fee_structure.update'); $s->updateStructure($structure,$college->university,$college,$r->validate($this->structureRules(true)),$r->user()->id,$r->ip()); return back()->with('toast',['type'=>'success','message'=>'College Fee Structure updated.']); }
    public function statusCollegeStructure(Request $r, College $college, FeeStructure $structure, FeeFoundationService $s): RedirectResponse { $status=$r->validate(['status'=>['required',Rule::in(['ACTIVE','INACTIVE'])]])['status']; $this->c($r,$college,$status==='ACTIVE'?'college_fee_structure.enable':'college_fee_structure.disable'); $s->changeStructureStatus($structure,$college->university,$college,$status,$r->user()->id,$r->ip()); return back()->with('toast',['type'=>'success','message'=>'College Fee Structure status updated.']); }
    public function adoptUniversityStructure(Request $r, College $college, FeeStructure $structure, FeeFoundationService $s): RedirectResponse { $this->c($r,$college,'college_fee_structure.adopt'); $adopt=$r->validate(['adopt'=>['required','boolean']])['adopt']; $s->setCollegeStructureAdoption($structure,$college,(bool)$adopt,$r->user()->id,$r->ip()); return back()->with('toast',['type'=>'success','message'=>$adopt?'University Fee Structure adopted.':'Optional University Fee Structure is no longer adopted.']); }
    public function storeCollegeItem(Request $r, College $college, FeeStructure $structure, FeeFoundationService $s): RedirectResponse { $this->c($r,$college,'college_fee_structure.update'); $s->upsertItem($structure,null,$college->university,$college,$r->validate($this->itemRules()),$r->user()->id,$r->ip()); return back()->with('toast',['type'=>'success','message'=>'Fee Item added.']); }
    public function updateCollegeItem(Request $r, College $college, FeeStructure $structure, FeeStructureItem $item, FeeFoundationService $s): RedirectResponse { $this->c($r,$college,'college_fee_structure.update'); $s->upsertItem($structure,$item,$college->university,$college,$r->validate($this->itemRules()),$r->user()->id,$r->ip()); return back()->with('toast',['type'=>'success','message'=>'Fee Item updated.']); }
    public function destroyCollegeItem(Request $r, College $college, FeeStructure $structure, FeeStructureItem $item, FeeFoundationService $s): RedirectResponse { $this->c($r,$college,'college_fee_structure.update'); $data=$r->validate(['period_no'=>['nullable','integer','min:1']]); $s->removeItemFromPeriod($structure,$item,$college->university,$college,isset($data['period_no'])?(int)$data['period_no']:null,$r->user()->id,$r->ip()); return back()->with('toast',['type'=>'success','message'=>'Fee Item removed from the billing period.']); }

    private function categories(int $universityId, ?int $collegeId)
    {
        return FeeCategory::where('university_id', $universityId)
            ->where(function ($q) use ($collegeId) {
                $q->whereNull('college_id');
                if ($collegeId) $q->orWhere('college_id', $collegeId);
            })
            ->orderBy('display_order')->orderBy('name')
            ->get()
            ->map(fn ($category) => $category->toArray() + ['owner_scope' => $category->college_id ? 'COLLEGE' : 'UNIVERSITY']);
    }

    private function heads(int $universityId, ?int $collegeId)
    {
        return FeeHead::with('category:id,name,code,status,college_id')
            ->where('university_id', $universityId)
            ->when(
                $collegeId,
                fn ($q) => $q->where(function ($scope) use ($collegeId) {
                    $scope->whereNull('college_id')->orWhere('college_id', $collegeId);
                }),
                fn ($q) => $q->whereNull('college_id')
            )
            ->orderByRaw('CASE WHEN college_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('name')
            ->get()
            ->map(fn ($head) => $head->toArray() + [
                'owner_scope' => $head->college_id ? 'COLLEGE' : 'UNIVERSITY',
            ]);
    }

    private function structures(int $universityId, ?int $collegeId)
    {
        return FeeStructure::with([
            'academicSession:id,name,code,status','programTemplate:id,name,code,term_structure,duration_terms','curriculum:id,program_template_id,academic_session_id,name,code,version,effective_from,effective_to,lifecycle_status,approval_status', 'curriculum.terms:id,curriculum_id,sequence_no,name,status',
            'offering.programTemplate:id,name,code,term_structure,duration_terms','offering.academicSession:id,name,code,status',
            'offering.curriculum.terms:id,curriculum_id,sequence_no,name,status',
            'items.head:id,name,code,status,fee_category_id','items.head.category:id,name,code,status','items.periodAmounts:id,fee_structure_item_id,period_no,amount','items.periodExclusions:id,fee_structure_item_id,period_no','items.periodSettings:id,fee_structure_item_id,period_no,due_date,is_mandatory,is_enrollment_clearance_required,installment_allowed,display_order,status',
        ])->where('university_id',$universityId)
            ->when($collegeId,fn($q)=>$q->where('college_id',$collegeId),fn($q)=>$q->whereNull('college_id'))
            ->orderByDesc('academic_session_id')->orderBy('purpose')->get()
            ->each(function ($structure) {
                $curriculumId=$structure->curriculum_id ?: $structure->offering?->curriculum_id;
                if(!$curriculumId){ $structure->setAttribute('academic_period_bounds', []); return; }
                $calendarId=\App\Models\AcademicCalendar::query()->where('university_id',$structure->university_id)->where('academic_session_id',$structure->academic_session_id)->where('status','ACTIVE')->value('id');
                $curriculum=$structure->curriculum ?: $structure->offering?->curriculum;
                $effectiveFrom=$curriculum?->effective_from?->format('Y-m-d');
                $effectiveTo=$curriculum?->effective_to?->format('Y-m-d');
                $bounds=$calendarId ? AcademicCalendarTermPeriod::query()->with('curriculumTerm:id,curriculum_id,sequence_no,name')->where('academic_calendar_id',$calendarId)->where('status','ACTIVE')->whereHas('curriculumTerm',fn($q)=>$q->where('curriculum_id',$curriculumId))->get()->map(function($p) use($effectiveFrom,$effectiveTo){
                    $start=$p->start_date->format('Y-m-d'); $end=$p->end_date->format('Y-m-d');
                    if($effectiveFrom && $start<$effectiveFrom) $start=$effectiveFrom;
                    if($effectiveTo && $end>$effectiveTo) $end=$effectiveTo;
                    return ['period_no'=>$p->curriculumTerm->sequence_no,'name'=>$p->curriculumTerm->name,'start_date'=>$start,'end_date'=>$end,'curriculum_effective_from'=>$effectiveFrom,'curriculum_effective_to'=>$effectiveTo];
                })->filter(fn($b)=>$b['start_date']<=$b['end_date'])->values()->all() : [];
                $structure->setAttribute('academic_period_bounds',$bounds);
            });
    }


    private function universityStructuresForCollege(int $universityId, College $college)
    {
        $offerings = CollegeProgramOffering::where('college_id', $college->id)
            ->where('status', 'ACTIVE')
            ->get(['id','academic_session_id','program_template_id','curriculum_id']);
        $adoptions = \App\Models\CollegeFeeStructureAdoption::where('college_id', $college->id)->pluck('status', 'university_fee_structure_id');

        return $this->structures($universityId, null)
            ->where('status', 'ACTIVE')
            ->filter(function ($structure) use ($offerings) {
                return $offerings->contains(function ($offering) use ($structure) {
                    return (int) $offering->academic_session_id === (int) $structure->academic_session_id
                        && (! $structure->program_template_id || (int) $offering->program_template_id === (int) $structure->program_template_id)
                        && (! $structure->curriculum_id || (int) $offering->curriculum_id === (int) $structure->curriculum_id);
                });
            })
            ->values()
            ->map(function ($structure) use ($adoptions) {
                $row = $structure->toArray();
                $row['adoption_status'] = $adoptions[$structure->id] ?? null;
                $row['effective_for_college'] = $structure->college_applicability === 'MANDATORY' || ($row['adoption_status'] === 'ADOPTED');
                return $row;
            });
    }

    private function categoryRules(): array { return ['name'=>['required','string','max:120'],'code'=>['required','string','max:40'],'description'=>['nullable','string','max:1000'],'display_order'=>['nullable','integer','min:0','max:65535']]; }
    private function headRules(): array { return ['name'=>['required','string','max:120'],'code'=>['required','string','max:40'],'fee_category_id'=>['required','integer'],'description'=>['nullable','string','max:1000'],'is_refundable'=>['nullable','boolean']]; }
    private function structureRules(bool $college): array { $rules=['name'=>['required','string','max:160'],'code'=>['required','string','max:50'],'purpose'=>['required',Rule::in(['ADMISSION','ACADEMIC','EXAMINATION','OTHER'])],'charge_basis'=>['required',Rule::in(['ONE_TIME','PER_TERM','PER_ACADEMIC_YEAR','SPECIFIC_TERM','SPECIFIC_ACADEMIC_YEAR'])],'charge_period_no'=>['nullable','integer','min:1','max:30'],'currency'=>['required','string','size:3'],'notes'=>['nullable','string','max:1500']]; if($college)$rules['college_program_offering_id']=['required','integer']; else {$rules['academic_session_id']=['required','integer'];$rules['program_template_id']=['nullable','integer'];$rules['curriculum_id']=['nullable','integer'];$rules['college_applicability']=['required',Rule::in(['MANDATORY','OPTIONAL'])];} return $rules; }
    private function itemRules(): array { return ['fee_head_id'=>['required','integer'],'amount'=>['required','numeric','gt:0','max:999999999.99'],'due_date'=>['nullable','date'],'period_amounts'=>['nullable','array'],'period_amounts.*'=>['nullable','numeric','gt:0','max:999999999.99'],'period_applicable'=>['nullable','array'],'period_applicable.*'=>['nullable','boolean'],'period_settings'=>['nullable','array'],'period_settings.*'=>['nullable','array'],'period_settings.*.due_date'=>['nullable','date'],'period_settings.*.is_mandatory'=>['nullable','boolean'],'period_settings.*.is_enrollment_clearance_required'=>['nullable','boolean'],'period_settings.*.installment_allowed'=>['nullable','boolean'],'period_settings.*.display_order'=>['nullable','integer','min:0','max:65535'],'period_settings.*.status'=>['nullable',Rule::in(['ACTIVE','INACTIVE'])],'is_mandatory'=>['nullable','boolean'],'is_enrollment_clearance_required'=>['nullable','boolean'],'installment_allowed'=>['nullable','boolean'],'display_order'=>['nullable','integer','min:0','max:65535'],'status'=>['required',Rule::in(['ACTIVE','INACTIVE'])]]; }
    private function u(Request $r,string $p):void{abort_unless($r->user()->hasPermission($p),403);} private function c(Request $r,College $c,string $p):void{abort_unless($r->user()->hasCollegePermission($p,$c->id),403);}
}
