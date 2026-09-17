<?php

namespace App\Services;

use App\Models\College;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentIdentitySetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentIdentityService
{
    public function assign(StudentEnrollment $enrollment, int $actorId, ?string $ip = null): StudentEnrollment
    {
        return DB::transaction(function () use ($enrollment, $actorId, $ip) {
            $enrollment = StudentEnrollment::query()->whereKey($enrollment->id)->lockForUpdate()->firstOrFail();
            $student = Student::query()->whereKey($enrollment->student_id)->lockForUpdate()->firstOrFail();
            $enrollment->loadMissing(['college','offering.programTemplate','offering.academicSession','discipline','admission.application.academicPreference']);
            $college = $enrollment->college;
            if ((int)$student->college_id !== (int)$college->id || $enrollment->status !== 'ENROLLED') {
                throw ValidationException::withMessages(['identity'=>'Only an active Student Enrollment can receive institutional identities.']);
            }
            $settings = StudentIdentitySetting::forCollege((int)$college->id);
            $before=['student_uid'=>$student->student_uid,'university_roll_no'=>$student->university_roll_no,'class_roll_no'=>$enrollment->class_roll_no];

            if (!$student->student_uid) $student->student_uid=$this->next($college,$enrollment,'STUDENT_UID',$settings->student_uid_format,'COLLEGE');
            if (!$student->university_roll_no) $student->university_roll_no=$this->next($college,$enrollment,'UNIVERSITY_ROLL',$settings->university_roll_format,'COLLEGE');
            $student->updated_by=$actorId; $student->save();
            if (!$enrollment->class_roll_no) {
                $scopeKey=$this->classRollScopeKey($enrollment, (string)$settings->class_roll_scope);
                $enrollment->class_roll_no=$this->next($college,$enrollment,'CLASS_ROLL',$settings->class_roll_format,$scopeKey);
                $enrollment->class_roll_scope_key=$scopeKey;
                $enrollment->save();
            }
            $after=['student_uid'=>$student->student_uid,'university_roll_no'=>$student->university_roll_no,'class_roll_no'=>$enrollment->class_roll_no];
            if ($before !== $after) DB::table('audit_logs')->insert([
                'actor_user_id'=>$actorId,'event'=>'student.identity.assigned','resource_type'=>'student_enrollment','resource_id'=>$enrollment->id,
                'scope_type'=>'COLLEGE','scope_reference'=>'college:'.$college->id,'before'=>json_encode($before),'after'=>json_encode($after),'ip_address'=>$ip,'created_at'=>now(),
            ]);
            return $enrollment->fresh(['student']);
        });
    }

    private function classRollScopeKey(StudentEnrollment $enrollment, string $scope): string
    {
        if ($scope === 'DISCIPLINE') {
            $disciplineId = $enrollment->discipline_id ?: $enrollment->admission?->application?->academicPreference?->discipline_id;
            if (!$disciplineId) {
                throw ValidationException::withMessages(['identity'=>'Class Roll Scope is Discipline, but this enrollment has no authoritative Discipline.']);
            }
            return 'OFFERING:'.$enrollment->college_program_offering_id.'|DISCIPLINE:'.$disciplineId;
        }
        return 'OFFERING:'.$enrollment->college_program_offering_id;
    }

    private function next(College $college, StudentEnrollment $enrollment, string $type, string $format, string $scope): string
    {
        if (!preg_match('/\{SEQ(?::\d{1,2})?\}/',$format)) throw ValidationException::withMessages(['identity'=>'Identity format must include a {SEQ} token.']);
        DB::table('student_identity_sequences')->insertOrIgnore(['college_id'=>$college->id,'identity_type'=>$type,'scope_key'=>$scope,'next_value'=>1,'created_at'=>now(),'updated_at'=>now()]);
        $row=DB::table('student_identity_sequences')->where(['college_id'=>$college->id,'identity_type'=>$type,'scope_key'=>$scope])->lockForUpdate()->first();
        $seq=(int)$row->next_value;
        $value=$this->render($format,$college,$enrollment,$seq);
        if ($value==='' || strlen($value)>120) throw ValidationException::withMessages(['identity'=>'Identity format generated an invalid value.']);
        DB::table('student_identity_sequences')->where('id',$row->id)->update(['next_value'=>$seq+1,'updated_at'=>now()]);
        return $value;
    }

    private function render(string $format, College $college, StudentEnrollment $enrollment, int $seq): string
    {
        $offering=$enrollment->offering; $session=$offering?->academicSession; $program=$offering?->programTemplate;
        $year=(string)($session?->starts_on?->format('Y') ?? now()->format('Y'));
        $tokens=['{COLLEGE_CODE}'=>$college->code,'{COLLEGE_ID}'=>(string)$college->id,'{YEAR}'=>$year,'{YY}'=>substr($year,-2),'{SESSION_CODE}'=>$session?->code ?? '', '{PROGRAM_CODE}'=>$program?->code ?? ''];
        $value=strtr($format,$tokens);
        return trim((string)preg_replace_callback('/\{SEQ(?::(\d{1,2}))?\}/',fn($m)=>str_pad((string)$seq,(int)($m[1]??6),'0',STR_PAD_LEFT),$value));
    }
}
