import { Form, Head, Link, router } from '@inertiajs/react';
import { Check, ChevronLeft, ChevronRight, ClipboardCheck, Pencil, Plus, Search, Send, Trash2, Undo2, UserCheck, UserX, XCircle } from 'lucide-react';
import { FormEvent, useEffect, useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { DatePicker } from '@/components/ui/date-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

type Cycle = {
    id:number; college_program_offering_id:number|null; academic_session_id:number; name:string; code:string;
    application_start_date:string; application_end_date:string; admission_start_date:string; admission_end_date:string;
    status:'INACTIVE'|'ACTIVE'|'CLOSED';
    academic_session:{id:number;name:string;code:string;is_current:boolean};
    program_offering?:{id:number;program_template:{name:string;code:string};academic_session:{name:string;code:string;is_current:boolean}}|null;
};

type SelectionContext = {
    college_admission_selection_rule_id:number;
    college_program_intake_id:number;
    college_program_offering_id:number;
    academic_session_id:number;
    program_name:string; program_code:string; session_name:string; session_code:string; is_current_session:boolean;
    bucket_key:string; bucket_type:string; bucket_label:string; basis_capacity:number;
    reservation_plan_id:number|null; reservation_state:'ACTIVE'|'NOT_DEFINED'|'AVAILABLE';
    rule_name:string|null; rule_code:string|null; rule_version:number|null; selection_mode:string;
    merit_weight_percent:string; entrance_weight_percent:string; interview_weight_percent:string;
};

type Choice = {
    id:number; preference_no:number; college_program_intake_id:number; college_program_reservation_plan_id:number|null;
    college_admission_selection_rule_id:number|null; bucket_type:string; bucket_key:string; bucket_label:string; basis_capacity:number;
    eligibility_status:'PENDING'|'ELIGIBLE'|'INELIGIBLE'; eligibility_reason?:string|null; eligibility_checked_at?:string|null;
    intake:{offering:{id:number;program_template:{name:string;code:string};academic_session:{name:string;code:string;is_current:boolean}}};
    reservation_plan?:{id:number;status:string}|null;
    selection_rule?:{id:number;name:string;code:string;version_no:number;selection_mode:string;status:string;merit_weight_percent:string;entrance_weight_percent:string;interview_weight_percent:string}|null;
};

type DynamicOption={value:string;label:string};
type DynamicCondition={source_field_id:number;operator:'EQUALS'|'NOT_EQUALS'|'IN'|'NOT_IN'|'CONTAINS'|'IS_EMPTY'|'IS_NOT_EMPTY';compare_values:string[]};
type DynamicPanel={id:number;title:string;code:string;description?:string|null;display_order:number};
type DynamicField={id:number;field_key:string;label:string;field_type:string;placeholder?:string|null;help_text?:string|null;is_required:boolean;college_admission_form_panel_id?:number|null;validation_rules?:Record<string,unknown>|null;condition_match_mode?:'ALL'|'ANY';conditions?:DynamicCondition[];options:DynamicOption[];comparison_rule?:{source_field_id:number;source_field_label?:string|null;operator:string}|null;copy_rule?:{source_field_id:number;trigger_field_id:number;trigger_values:string[];read_only:boolean}|null};
type DynamicStep={id:number;title:string;code:string;description?:string|null;panels?:DynamicPanel[];fields:DynamicField[]};
type FormTemplate={id:number;name:string;code:string;admission_mode:string;governance_mode:string;steps:DynamicStep[]};
type FormConfig={template:FormTemplate|null;fee:{required:boolean;amount:string;currency:string;rule_id:number|null}};

type AcademicMapping={id:number;course_id:number;discipline_id:number|null;specialization_id:number|null;course_name:string;course_code:string;source_discipline_id?:number|null;source_discipline_name?:string|null;source_discipline_code?:string|null};
type AcademicSlot={id:number;name:string;selection_mode:'MANDATORY'|'CHOICE';min_selection:number|null;max_selection:number|null;credits:string|null;category_name:string|null;type_name:string|null;mappings:AcademicMapping[]};
type AcademicTerm={id:number;name:string;sequence_no:number;slots:AcademicSlot[]};
type AcademicDisciplineOption={id:number;name:string;code:string;specialization_required?:boolean;specializations:{id:number;name:string;code:string}[]};
type AcademicOptions={curriculum:{id:number;name:string;code:string;version:string}|null;disciplines:AcademicDisciplineOption[];terms:AcademicTerm[]};

type ChoiceSourcePackage={key:string;name:string;bySlot:Record<number,AcademicMapping[]>};
type ChoiceCategory={name:string;slots:AcademicSlot[];packageMode:boolean;packages:ChoiceSourcePackage[]};
const academicSourceKey=(mapping:AcademicMapping)=>mapping.source_discipline_id?`discipline:${mapping.source_discipline_id}`:'common';
const academicSourceName=(mapping:AcademicMapping)=>mapping.source_discipline_name??'Common / Interdisciplinary';
const sameAcademicIds=(a:number[],b:number[])=>[...a].sort((x,y)=>x-y).join(',')===[...b].sort((x,y)=>x-y).join(',');
function buildAcademicChoiceCategories(terms:AcademicTerm[]):ChoiceCategory[]{
    const grouped=new Map<string,AcademicSlot[]>();
    terms.forEach(term=>term.slots.forEach(slot=>{const key=slot.category_name??'Choice / Elective';grouped.set(key,[...(grouped.get(key)??[]),slot]);}));
    return Array.from(grouped.entries()).map(([name,slots])=>{
        const keys=Array.from(new Set(slots.flatMap(slot=>slot.mappings.map(academicSourceKey))));
        const packages=keys.map(key=>{
            const bySlot:Record<number,AcademicMapping[]>={};let valid=true;let label='Common / Interdisciplinary';
            for(const slot of slots){const min=slot.min_selection??1;const max=slot.max_selection??min;const mappings=slot.mappings.filter(m=>academicSourceKey(m)===key);if(min!==max||mappings.length!==min||mappings.length===0){valid=false;break;}bySlot[slot.id]=mappings;if(mappings[0])label=academicSourceName(mappings[0]);}
            return valid?{key,name:label,bySlot}:null;
        }).filter(Boolean) as ChoiceSourcePackage[];
        return {name,slots,packageMode:keys.length>0&&packages.length===keys.length,packages};
    });
}

type Application = {
    id:number; college_id:number; college_admission_cycle_id:number; application_no:string; external_reference?:string|null; admission_mode:'REGULAR'|'DIRECT'; entry_source?:'INTERNAL'|'PUBLIC';
    candidate_name:string; email?:string|null; phone?:string|null; date_of_birth:string; status:'DRAFT'|'SUBMITTED'|'WITHDRAWN';
    submitted_at?:string|null; withdrawn_at?:string|null; remarks?:string|null;
    admission_cycle:Cycle; choices:Choice[]; form_snapshot?:FormTemplate|null;
    field_values?:{college_admission_form_field_id:number;value_text?:string|null;value_json?:string[]|null;file_name?:string|null;file_path?:string|null}[];
    application_fee_required?:boolean;application_fee_amount?:string;application_fee_currency?:string;
    academic_preference?:{discipline_id:number|null;specialization_id:number|null;discipline?:{id:number;name:string;code:string}|null;specialization?:{id:number;name:string;code:string}|null}|null;
    course_choices?:{curriculum_slot_id:number;curriculum_course_mapping_id:number;selection_source:'AUTO_MANDATORY'|'APPLICANT_CHOICE';course?:{id:number;name:string;code:string}|null}[];
};

type Paginated<T> = { data:T[]; current_page:number; last_page:number; prev_page_url:string|null; next_page_url:string|null; total:number };
type Props = {
    college:{id:number;name:string;code:string;status:string}; cycles:Cycle[]; selectionContexts:SelectionContext[]; directSelectionContexts:SelectionContext[];
    formConfigs:Record<string,Record<'REGULAR'|'DIRECT',FormConfig>>; academicOptions:Record<string,AcademicOptions>; applications:Paginated<Application>; filters:{search:string};
    can:{create:boolean;update:boolean;submit:boolean;eligibility:boolean;withdraw:boolean};
};
type SearchableOption = {value:string;label:string;searchText?:string;meta?:string};

function SearchablePicker({label,value,onChange,options,placeholder,disabled=false}:{label:string;value:string;onChange:(value:string)=>void;options:SearchableOption[];placeholder:string;disabled?:boolean}){
    const selected=options.find(option=>option.value===value);
    const [open,setOpen]=useState(false);
    const [query,setQuery]=useState('');
    const filtered=useMemo(()=>{
        const needle=query.trim().toLowerCase();
        if(!needle) return options;
        return options.filter(option=>`${option.label} ${option.searchText??''} ${option.meta??''}`.toLowerCase().includes(needle));
    },[options,query]);
    return <div className="relative min-w-0 space-y-2">
        <Label>{label}</Label>
        <button type="button" disabled={disabled} onClick={()=>{setOpen(current=>!current);setQuery('');}} className="flex min-h-10 w-full min-w-0 items-center justify-between gap-3 rounded-md border bg-background px-3 py-2 text-left text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50">
            <span className={selected?'min-w-0 truncate':'min-w-0 truncate text-muted-foreground'}>{selected?.label??placeholder}</span><Search className="size-4 shrink-0 text-muted-foreground"/>
        </button>
        {open&&<div className="absolute z-50 mt-1 w-full min-w-0 overflow-hidden rounded-md border bg-popover text-popover-foreground shadow-md">
            <div className="border-b p-2"><div className="relative"><Search className="absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"/><Input autoFocus value={query} onChange={e=>setQuery(e.target.value)} placeholder={`Search ${label.toLowerCase()}...`} className="w-full pl-8" onKeyDown={e=>{if(e.key==='Escape')setOpen(false);}}/></div></div>
            <div className="max-h-64 overflow-y-auto p-1">{filtered.length===0?<div className="px-3 py-6 text-center text-sm text-muted-foreground">No matching option found.</div>:filtered.map(option=><button key={option.value} type="button" onMouseDown={e=>e.preventDefault()} onClick={()=>{onChange(option.value);setOpen(false);setQuery('');}} className="flex w-full min-w-0 items-start gap-2 rounded-sm px-2 py-2 text-left text-sm hover:bg-accent hover:text-accent-foreground"><Check className={`mt-0.5 size-4 shrink-0 ${option.value===value?'opacity-100':'opacity-0'}`}/><span className="min-w-0 flex-1"><span className="block break-words">{option.label}</span>{option.meta&&<span className="mt-0.5 block text-xs text-muted-foreground">{option.meta}</span>}</span></button>)}</div>
        </div>}
    </div>;
}

function reservationText(context:SelectionContext){return context.reservation_state==='ACTIVE'?'Reservation ACTIVE':'No Reservation · Open/General';}
function ruleText(context:SelectionContext){return context.rule_code?`${context.rule_code} v${context.rule_version} · ${context.selection_mode} · M ${context.merit_weight_percent}% / E ${context.entrance_weight_percent}% / I ${context.interview_weight_percent}%`:'Direct Admission · Selection Rule not required';}

const canonicalConditionValue=(value:unknown)=>String(value??'').trim().toLowerCase().replace(/[^a-z0-9]+/g,'_').replace(/^_+|_+$/g,'');
function conditionMatches(actual:string|string[]|undefined,condition:DynamicCondition){
    const values=Array.isArray(actual)?actual:[actual??''];
    const blank=values.every(v=>String(v).trim()==='');
    if(condition.operator==='IS_EMPTY')return blank;
    if(condition.operator==='IS_NOT_EMPTY')return !blank;
    const expected=condition.compare_values.map(String);
    const actualCanonical=values.map(canonicalConditionValue);
    const expectedCanonical=expected.map(canonicalConditionValue);
    const intersects=actualCanonical.some(v=>expectedCanonical.includes(v));
    if(condition.operator==='EQUALS'||condition.operator==='IN')return intersects;
    if(condition.operator==='NOT_EQUALS'||condition.operator==='NOT_IN')return !intersects;
    if(condition.operator==='CONTAINS')return values.map(String).some(v=>expected.some(e=>e&&v.toLowerCase().includes(e.toLowerCase())));
    return false;
}

function fieldVisible(field:DynamicField,values:Record<number,string|string[]>){
    const conditions=field.conditions??[];
    if(!conditions.length)return true;
    const results=conditions.map(c=>conditionMatches(values[c.source_field_id],c));
    return field.condition_match_mode==='ANY'?results.some(Boolean):results.every(Boolean);
}

function textInputConstraint(mode:unknown,value:string){
    if(!value||!mode||mode==='ANY')return '';
    if(mode==='LETTERS_ONLY'&&!/^[\p{L}\p{M}]+(?:[ '\-][\p{L}\p{M}]+)*$/u.test(value))return 'Use letters only.';
    if(mode==='DIGITS_ONLY'&&!/^\d+$/u.test(value))return 'Use digits only.';
    if(mode==='ALPHANUMERIC'&&!/^[\p{L}\p{M}\p{N}]+$/u.test(value))return 'Use letters and numbers only.';
    return '';
}

function DynamicInput({field,application,value,onChange,copyLocked=false,error}:{field:DynamicField;application?:Application;value:string|string[];onChange:(value:string|string[])=>void;copyLocked?:boolean;error?:string}){
    const existing=application?.field_values?.find(v=>v.college_admission_form_field_id===field.id);
    const base=`custom_fields[${field.id}]`;
    const label=<Label>{field.label}{field.is_required&&<span className="text-destructive"> *</span>}</Label>;const vr=field.validation_rules??{};const exact=Number(vr.exact_length??0)||undefined;const minLength=exact??(Number(vr.min_length??0)||undefined);const maxLength=exact??(Number(vr.max_length??0)||undefined);const textMode=String(vr.text_input_mode??'ANY');const textInputMode=textMode==='DIGITS_ONLY'?'numeric':undefined;const validateTextInput=(element:HTMLInputElement|HTMLTextAreaElement,nextValue:string)=>element.setCustomValidity(textInputConstraint(textMode,nextValue));
    if(field.field_type==='TEXTAREA')return <div className="space-y-2">{label}<textarea name={base} value={String(value??'')} onChange={e=>{validateTextInput(e.currentTarget,e.target.value);onChange(e.target.value)}} onInvalid={e=>validateTextInput(e.currentTarget,String(value??''))} disabled={copyLocked} minLength={minLength} maxLength={maxLength} className="min-h-24 w-full rounded-md border bg-background px-3 py-2 text-sm" placeholder={field.placeholder??''}/>{field.help_text&&<p className="text-xs text-muted-foreground">{field.help_text}</p>}{error&&<p className="text-xs text-destructive">{error}</p>}</div>;
    if(field.field_type==='SELECT')return <div className="space-y-2">{label}<select name={base} value={String(value??'')} disabled={copyLocked} onChange={e=>onChange(e.target.value)} className="h-10 w-full rounded-md border bg-background px-3 text-sm"><option value="">Select...</option>{field.options.map(o=><option key={o.value} value={o.value}>{o.label}</option>)}</select>{field.help_text&&<p className="text-xs text-muted-foreground">{field.help_text}</p>}{error&&<p className="text-xs text-destructive">{error}</p>}</div>;
    if(field.field_type==='RADIO'||field.field_type==='YES_NO')return <div className="space-y-2">{label}<div className="flex flex-wrap gap-4">{field.options.map(o=><label key={o.value} className="flex items-center gap-2 text-sm"><input type="radio" name={base} value={o.value} disabled={copyLocked} checked={String(value??'')===o.value} onChange={()=>onChange(o.value)} className="size-4"/>{o.label}</label>)}</div>{field.help_text&&<p className="text-xs text-muted-foreground">{field.help_text}</p>}{error&&<p className="text-xs text-destructive">{error}</p>}</div>;
    if(field.field_type==='CHECKBOX'||field.field_type==='MULTISELECT'){const values=Array.isArray(value)?value:[];return <div className="space-y-2">{label}<div className="flex flex-wrap gap-4">{field.options.map(o=><label key={o.value} className="flex items-center gap-2 text-sm"><input type="checkbox" name={`${base}[]`} value={o.value} disabled={copyLocked} checked={values.includes(o.value)} onChange={e=>onChange(e.target.checked?[...values,o.value]:values.filter(v=>v!==o.value))} className="size-4 rounded"/>{o.label}</label>)}</div>{field.help_text&&<p className="text-xs text-muted-foreground">{field.help_text}</p>}{error&&<p className="text-xs text-destructive">{error}</p>}</div>}
    if(field.field_type==='FILE'||field.field_type==='IMAGE')return <div className="space-y-2">{label}<Input name={base} type="file" accept={field.field_type==='IMAGE'?'image/*':undefined}/>{existing?.file_name&&<p className="text-xs text-muted-foreground">Existing: {existing.file_name}. Upload a new file only to replace it.</p>}{field.help_text&&<p className="text-xs text-muted-foreground">{field.help_text}</p>}{error&&<p className="text-xs text-destructive">{error}</p>}</div>;
    if(field.field_type==='DATE')return <div className="space-y-2">{label}<DatePicker id={`dynamic-field-${field.id}`} name={base} value={String(value??'')} onValueChange={onChange}/>{field.help_text&&<p className="text-xs text-muted-foreground">{field.help_text}</p>}{error&&<p className="text-xs text-destructive">{error}</p>}</div>;
    const inputType=field.field_type==='NUMBER'?'number':field.field_type==='EMAIL'?'email':field.field_type==='PHONE'?'tel':'text';
    return <div className="space-y-2">{label}<Input name={base} type={inputType} value={String(value??'')} disabled={copyLocked} minLength={minLength} maxLength={maxLength} min={field.field_type==='NUMBER'&&vr.min_value!==undefined?Number(vr.min_value):undefined} max={field.field_type==='NUMBER'&&vr.max_value!==undefined?Number(vr.max_value):undefined} step={field.field_type==='NUMBER'?(vr.integer_only?'1':vr.decimal_places!==undefined?String(1/Math.pow(10,Number(vr.decimal_places))):'any'):undefined} inputMode={field.field_type==='TEXT'?textInputMode:undefined} onChange={e=>{if(field.field_type==='TEXT')validateTextInput(e.currentTarget,e.target.value);onChange(e.target.value)}} onInvalid={e=>{if(field.field_type==='TEXT')validateTextInput(e.currentTarget,String(value??''))}} placeholder={field.placeholder??''}/>{field.help_text&&<p className="text-xs text-muted-foreground">{field.help_text}</p>}{error&&<p className="text-xs text-destructive">{error}</p>}</div>;
}

function ApplicationForm({collegeId,cycles,formConfigs,academicOptions,application}:{collegeId:number;cycles:Cycle[];formConfigs:Record<string,Record<'REGULAR'|'DIRECT',FormConfig>>;academicOptions:Record<string,AcademicOptions>;application?:Application}){
    const activeCycles=cycles.filter(c=>c.status==='ACTIVE'&&c.college_program_offering_id);
    const [admissionMode,setAdmissionMode]=useState<'REGULAR'|'DIRECT'>(application?.admission_mode??'REGULAR');
    const initialCycle=application?.college_admission_cycle_id?.toString()??activeCycles[0]?.id.toString()??'';
    const [cycleId,setCycleId]=useState(initialCycle);
    const cycleFormConfigs=formConfigs[String(cycleId)];
    const configuredAdmissionModes=(['REGULAR','DIRECT'] as const).filter(mode=>Boolean(cycleFormConfigs?.[mode]?.template));
    const allowedAdmissionModes:('REGULAR'|'DIRECT')[]=configuredAdmissionModes.length>0?[...configuredAdmissionModes]:['REGULAR','DIRECT'];
    const formConfig=cycleFormConfigs?.[admissionMode];
    const dynamicTemplate=formConfig?.template??null;
    const academic=academicOptions[String(cycleId)]??{curriculum:null,disciplines:[],terms:[]};
    const initialDynamicValues=()=>Object.fromEntries((application?.field_values??[]).map(v=>[v.college_admission_form_field_id,v.value_json??v.value_text??'']));
    const [dynamicValues,setDynamicValues]=useState<Record<number,string|string[]>>(initialDynamicValues);
    const setDynamicValue=(fieldId:number,value:string|string[])=>setDynamicValues(current=>({...current,[fieldId]:value}));
    useEffect(()=>{
        if(!allowedAdmissionModes.includes(admissionMode)){
            setAdmissionMode(allowedAdmissionModes[0]??'REGULAR');
        }
    },[cycleId,admissionMode,allowedAdmissionModes.join('|')]);

    useEffect(()=>{setDynamicValues(current=>{let changed=false;const next={...current};(dynamicTemplate?.steps??[]).flatMap(step=>step.fields??[]).forEach(field=>{const rule=field.copy_rule;if(!rule)return;const trigger=next[rule.trigger_field_id];const expected=(rule.trigger_values??[]).map(canonicalConditionValue);const actual=(Array.isArray(trigger)?trigger:[trigger??'']).map(v=>canonicalConditionValue(v));if(!actual.some(v=>expected.includes(v)))return;const source=next[rule.source_field_id]??'';if(JSON.stringify(next[field.id]??'')!==JSON.stringify(source)){next[field.id]=source;changed=true;}});return changed?next:current;});},[dynamicValues,dynamicTemplate]);
    const [disciplineId,setDisciplineId]=useState(application?.academic_preference?.discipline_id?String(application.academic_preference.discipline_id):'');
    const [specializationId,setSpecializationId]=useState(application?.academic_preference?.specialization_id?String(application.academic_preference.specialization_id):'');
    const initialCourseChoices=()=>{
        const result:Record<number,number[]>={};
        (application?.course_choices??[]).filter(c=>c.selection_source==='APPLICANT_CHOICE').forEach(c=>{
            result[c.curriculum_slot_id]=[...(result[c.curriculum_slot_id]??[]),c.curriculum_course_mapping_id];
        });
        return result;
    };
    const [courseChoices,setCourseChoices]=useState<Record<number,number[]>>(initialCourseChoices);
    const selectedDiscipline=academic.disciplines.find(d=>String(d.id)===disciplineId);
    const specializations=selectedDiscipline?.specializations??[];
    const applicableMappings=(slot:AcademicSlot)=>(slot.mappings??[]).filter(m=>m.discipline_id===null?(m.specialization_id===null):(String(m.discipline_id)===disciplineId&&(m.specialization_id===null||String(m.specialization_id)===specializationId)));
    const choiceTerms=(academic.terms??[]).map(term=>({...term,slots:(term.slots??[]).filter(slot=>slot.selection_mode==='CHOICE').map(slot=>({...slot,mappings:applicableMappings(slot)})).filter(slot=>slot.mappings.length>0)})).filter(term=>term.slots.length>0);
    const choiceCategories=buildAcademicChoiceCategories(choiceTerms);
    const mandatoryCount=(academic.terms??[]).reduce((total,term)=>total+(term.slots??[]).filter(slot=>slot.selection_mode==='MANDATORY').reduce((sum,slot)=>sum+applicableMappings(slot).length,0),0);
    const toggleChoice=(slot:AcademicSlot,mappingId:number)=>setCourseChoices(current=>{const existing=current[slot.id]??[];const max=slot.max_selection??slot.min_selection??1;if(existing.includes(mappingId))return {...current,[slot.id]:existing.filter(id=>id!==mappingId)};if(existing.length>=max)return current;return {...current,[slot.id]:[...existing,mappingId]};});
    const selectPackage=(category:ChoiceCategory,pkg:ChoiceSourcePackage)=>setCourseChoices(current=>{const next={...current};category.slots.forEach(slot=>{next[slot.id]=(pkg.bySlot[slot.id]??[]).map(m=>m.id);});return next;});
    const packageSelected=(category:ChoiceCategory,pkg:ChoiceSourcePackage)=>category.slots.every(slot=>sameAcademicIds(courseChoices[slot.id]??[],(pkg.bySlot[slot.id]??[]).map(m=>m.id)));
    const cycleOptions=activeCycles.map(c=>({value:String(c.id),label:`${c.name} · ${c.program_offering?.program_template.name??'Program Offering required'} · ${c.academic_session.name}`,searchText:`${c.code} ${c.program_offering?.program_template.code??''} ${c.academic_session.code}`,meta:`Applications ${c.application_start_date?.slice(0,10)} → ${c.application_end_date?.slice(0,10)}`}));
    const action=application?`/college/${collegeId}/admission-applications/${application.id}`:`/college/${collegeId}/admission-applications`;
    const resetCycle=(value:string)=>{setCycleId(value);setDisciplineId('');setSpecializationId('');setCourseChoices({});};
    return <Dialog>
        <DialogTrigger asChild>{application?<Button size="sm" variant="outline"><Pencil/>Edit Draft</Button>:<Button><Plus/>Add Application</Button>}</DialogTrigger>
        <DialogContent className="flex max-h-[94vh] w-[calc(100vw-2rem)] max-w-none flex-col overflow-hidden sm:!max-w-5xl">
            <DialogHeader className="shrink-0"><DialogTitle>{application?'Edit Admission Application':'Add Admission Application'}</DialogTitle><DialogDescription>Use the same configured Admission Form and curriculum choices as the Applicant Portal. Seat capacity, reservation roster and seat allocation are handled later and do not block application entry.</DialogDescription></DialogHeader>
            <Form action={action} method={application?'patch':'post'} className="flex min-h-0 flex-1 flex-col overflow-hidden">
                {({processing,errors})=>{
                    const errorEntries=Object.entries(errors).filter(([key,message])=>Boolean(message)&&!key.startsWith('custom_fields.'));
                    return <>
                    <div className="min-h-0 flex-1 space-y-5 overflow-y-auto px-1 py-2 pr-2">
                        {errorEntries.length>0&&<div role="alert" className="rounded-xl border border-destructive/40 bg-destructive/5 p-4"><div className="font-medium text-destructive">Application could not be saved</div><p className="mt-1 text-sm text-muted-foreground">Please correct the following validation issue{errorEntries.length===1?'':'s'} and try again.</p><div className="mt-3 space-y-1">{errorEntries.map(([key,message])=><p key={key} className="text-sm text-destructive">• {String(message)}</p>)}</div></div>}
                        <SearchablePicker label="Admission Cycle" value={cycleId} onChange={resetCycle} options={cycleOptions} placeholder="Select active Admission Cycle"/>
                        <input type="hidden" name="college_admission_cycle_id" value={cycleId}/>
                        {errors.college_admission_cycle_id&&<p className="text-xs text-destructive">{errors.college_admission_cycle_id}</p>}
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-2"><Label>Admission Mode</Label><select name="admission_mode" value={admissionMode} onChange={e=>setAdmissionMode(e.target.value as 'REGULAR'|'DIRECT')} disabled={allowedAdmissionModes.length===1} className="h-10 w-full rounded-md border bg-background px-3 text-sm disabled:cursor-not-allowed disabled:opacity-70">{allowedAdmissionModes.includes('REGULAR')&&<option value="REGULAR">Regular / Selection Based</option>}{allowedAdmissionModes.includes('DIRECT')&&<option value="DIRECT">Direct Admission</option>}</select><p className="text-xs text-muted-foreground">{configuredAdmissionModes.length>0?(allowedAdmissionModes.length===1?`Restricted by the mapped Admission Form: ${allowedAdmissionModes[0]==='REGULAR'?'Regular / Selection Based only':'Direct Admission only'}.`:'The mapped Admission Form allows both Regular and Direct Admission routes.'):'No dynamic Admission Form is mapped, so core-only application capture currently allows both routes.'}</p></div>
                            <div className="rounded-xl border bg-muted/20 p-4 text-sm"><div className="font-medium">Resolved Form & Fee</div><p className="mt-1 text-muted-foreground">{dynamicTemplate?`${dynamicTemplate.name} (${dynamicTemplate.code})`:'No dynamic template mapped — core application fields only.'}</p><p className="mt-1 font-medium">{formConfig?.fee.required?`${formConfig.fee.currency} ${Number(formConfig.fee.amount).toFixed(2)} application fee`:'No application fee'}</p></div>
                        </div>
                        <div className="grid min-w-0 gap-4 sm:grid-cols-2">
                            <div className="min-w-0 space-y-2"><Label>Candidate Name</Label><Input className="w-full min-w-0" name="candidate_name" defaultValue={application?.candidate_name??''} placeholder="Full candidate name"/>{errors.candidate_name&&<p className="text-xs text-destructive">{errors.candidate_name}</p>}</div>
                            <div className="min-w-0 space-y-2"><Label>Date of Birth</Label><DatePicker id="admission-application-date-of-birth" name="date_of_birth" defaultValue={application?.date_of_birth?.slice(0,10)??''} max={new Date().toISOString().slice(0,10)} invalid={Boolean(errors.date_of_birth)}/>{errors.date_of_birth&&<p className="text-xs text-destructive">{errors.date_of_birth}</p>}</div>
                            <div className="min-w-0 space-y-2"><Label>Email <span className="text-muted-foreground">(optional)</span></Label><Input className="w-full min-w-0" name="email" type="email" defaultValue={application?.email??''}/>{errors.email&&<p className="text-xs text-destructive">{errors.email}</p>}</div>
                            <div className="min-w-0 space-y-2"><Label>Phone <span className="text-muted-foreground">(optional)</span></Label><Input className="w-full min-w-0" name="phone" defaultValue={application?.phone??''}/>{errors.phone&&<p className="text-xs text-destructive">{errors.phone}</p>}</div>
                            <div className="min-w-0 space-y-2 sm:col-span-2"><Label>External / Portal Reference <span className="text-muted-foreground">(optional)</span></Label><Input className="w-full min-w-0" name="external_reference" defaultValue={application?.external_reference??''}/></div>
                        </div>

                        <section className="space-y-4 rounded-2xl border bg-primary/[0.025] p-4">
                            <div><p className="text-xs font-semibold uppercase tracking-[0.18em] text-primary">Academic Selection</p><h3 className="mt-1 text-lg font-semibold">Program curriculum choices</h3><p className="text-sm text-muted-foreground">Mandatory curriculum is inherited automatically. Only choices actually configured in the approved curriculum are shown here.</p></div>
                            <div className={`grid gap-4 ${specializations.length?'sm:grid-cols-2':''}`}>
                                <div className="space-y-2"><Label>Discipline {academic.disciplines.length?'*':''}</Label><select name="academic_preference[discipline_id]" value={disciplineId} onChange={e=>{setDisciplineId(e.target.value);setSpecializationId('');setCourseChoices({});}} className="h-10 w-full rounded-md border bg-background px-3 text-sm" required={academic.disciplines.length>0}><option value="">{academic.disciplines.length?'Select Discipline':'Program-wide curriculum'}</option>{academic.disciplines.map(d=><option key={d.id} value={d.id}>{d.name} ({d.code})</option>)}</select>{errors['academic_preference.discipline_id']&&<p className="text-xs text-destructive">{errors['academic_preference.discipline_id']}</p>}</div>
                                {specializations.length>0&&<div className="space-y-2"><Label>Specialization {selectedDiscipline?.specialization_required?'*':'(Optional)'}</Label><select name="academic_preference[specialization_id]" value={specializationId} onChange={e=>{setSpecializationId(e.target.value);setCourseChoices({});}} className="h-10 w-full rounded-md border bg-background px-3 text-sm" required={Boolean(selectedDiscipline?.specialization_required)}><option value="">{selectedDiscipline?.specialization_required?'Select Specialization':'General / No Specialization'}</option>{specializations.map(sp=><option key={sp.id} value={sp.id}>{sp.name} ({sp.code})</option>)}</select>{errors['academic_preference.specialization_id']&&<p className="text-xs text-destructive">{errors['academic_preference.specialization_id']}</p>}</div>}
                            </div>
                            {disciplineId&&<div className="rounded-xl border bg-background/70 p-3 text-sm"><span className="font-medium">Mandatory curriculum:</span> {mandatoryCount>0?`${mandatoryCount} mapped paper(s) will be inherited automatically according to the selected Discipline${specializationId?' and Specialization':''}.`:'No mandatory mapped papers are currently applicable.'}</div>}
                            {disciplineId&&choiceCategories.map(category=>{
                                if(category.packageMode){return <div key={category.name} className="space-y-3 rounded-xl border bg-background p-4"><div className="flex flex-wrap items-start justify-between gap-2"><div><div className="font-medium">{category.name}</div><div className="text-xs text-muted-foreground">Choose the academic area once; all linked curriculum papers are attached internally.</div></div><span className="rounded-full bg-primary/10 px-2 py-1 text-xs font-medium text-primary">ACADEMIC PACKAGE</span></div><div className="grid gap-2 sm:grid-cols-2">{category.packages.map(pkg=>{const checked=packageSelected(category,pkg);return <label key={pkg.key} className={`cursor-pointer rounded-xl border p-3 transition ${checked?'border-primary/40 bg-primary/5':'hover:bg-muted/30'}`}><div className="flex items-center gap-3"><input type="radio" checked={checked} onChange={()=>selectPackage(category,pkg)} className="mt-0.5"/><span><span className="block font-medium">{pkg.name}</span><span className="text-xs text-muted-foreground">All configured papers under this option are linked automatically.</span></span></div>{checked&&category.slots.flatMap(slot=>(pkg.bySlot[slot.id]??[]).map(mapping=><input key={`${slot.id}-${mapping.id}`} type="hidden" name={`academic_preference[course_choices][${slot.id}][]`} value={mapping.id}/>))}</label>})}</div>{errors['academic_preference.course_choices']&&<p className="text-xs text-destructive">{errors['academic_preference.course_choices']}</p>}</div>}
                                const entries=category.slots.flatMap(slot=>slot.mappings.map(mapping=>({slot,mapping})));const groups=entries.reduce<Record<string,{slot:AcademicSlot;mapping:AcademicMapping}[]>>((acc,item)=>{const key=academicSourceName(item.mapping);(acc[key]??=[]).push(item);return acc;},{});return <div key={category.name} className="space-y-3 rounded-xl border bg-background p-4"><div><div className="font-medium">{category.name}</div><div className="text-xs text-muted-foreground">Only real course-level choices are listed; semester placement is handled internally.</div></div>{Object.entries(groups).map(([group,items])=><div key={group} className="space-y-2"><div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{group==='Common / Interdisciplinary'?group:`From ${group}`}</div><div className="grid gap-2 sm:grid-cols-2">{items.map(({slot,mapping})=>{const selected=courseChoices[slot.id]??[];const checked=selected.includes(mapping.id);const max=slot.max_selection??slot.min_selection??1;return <label key={`${slot.id}-${mapping.id}`} className={`flex cursor-pointer items-start gap-3 rounded-lg border p-3 ${checked?'border-primary/40 bg-primary/5':'hover:bg-muted/30'}`}><input type="checkbox" name={`academic_preference[course_choices][${slot.id}][]`} value={mapping.id} checked={checked} disabled={!checked&&selected.length>=max} onChange={()=>toggleChoice(slot,mapping.id)} className="mt-1"/><span><span className="block font-medium">{mapping.course_code} · {mapping.course_name}</span>{mapping.source_discipline_name&&<span className="text-xs text-muted-foreground">Offered from {mapping.source_discipline_name}</span>}</span></label>})}</div></div>)}{category.slots.map(slot=>errors[`academic_preference.course_choices.${slot.id}`]?<p key={slot.id} className="text-xs text-destructive">{errors[`academic_preference.course_choices.${slot.id}`]}</p>:null)}</div>;
                            })}

                        </section>

                        {dynamicTemplate?.steps.map((step,stepIndex)=>{const visibleFields=step.fields.filter(field=>fieldVisible(field,dynamicValues));if(!visibleFields.length)return null;const panels=step.panels??[];const directFields=visibleFields.filter(field=>!field.college_admission_form_panel_id);return <div key={step.id} className="space-y-4 rounded-2xl border p-4"><div><div className="text-xs font-semibold uppercase tracking-[0.15em] text-primary">Application Step {stepIndex+1} of {dynamicTemplate.steps.length}</div><h3 className="text-lg font-semibold">{step.title}</h3>{step.description&&<p className="text-sm text-muted-foreground">{step.description}</p>}</div>{panels.map(panel=>{const panelFields=visibleFields.filter(field=>field.college_admission_form_panel_id===panel.id);if(!panelFields.length)return null;return <div key={panel.id} className="space-y-3 rounded-xl border bg-muted/10 p-4"><div><h4 className="font-medium">{panel.title}</h4>{panel.description&&<p className="text-sm text-muted-foreground">{panel.description}</p>}</div><div className="grid gap-4 sm:grid-cols-2">{panelFields.map(field=><DynamicInput key={field.id} field={field} application={application} value={dynamicValues[field.id]??''} copyLocked={Boolean(field.copy_rule?.read_only&&(()=>{const rule=field.copy_rule!;const expected=(rule.trigger_values??[]).map(canonicalConditionValue);const trigger=dynamicValues[rule.trigger_field_id];const actual=(Array.isArray(trigger)?trigger:[trigger??'']).map(v=>canonicalConditionValue(v));return actual.some(v=>expected.includes(v));})())} onChange={value=>setDynamicValue(field.id,value)} error={errors[`custom_fields.${field.id}`] ? String(errors[`custom_fields.${field.id}`]) : undefined}/>)}</div></div>})}{directFields.length>0&&<div className="grid gap-4 sm:grid-cols-2">{directFields.map(field=><DynamicInput key={field.id} field={field} application={application} value={dynamicValues[field.id]??''} copyLocked={Boolean(field.copy_rule?.read_only&&(()=>{const rule=field.copy_rule!;const expected=(rule.trigger_values??[]).map(canonicalConditionValue);const trigger=dynamicValues[rule.trigger_field_id];const actual=(Array.isArray(trigger)?trigger:[trigger??'']).map(v=>canonicalConditionValue(v));return actual.some(v=>expected.includes(v));})())} onChange={value=>setDynamicValue(field.id,value)} error={errors[`custom_fields.${field.id}`] ? String(errors[`custom_fields.${field.id}`]) : undefined}/>)}</div>}</div>})}
                        <div className="space-y-2"><Label>Remarks <span className="text-muted-foreground">(optional)</span></Label><textarea name="remarks" defaultValue={application?.remarks??''} className="min-h-20 w-full min-w-0 resize-y rounded-md border bg-background px-3 py-2 text-sm"/></div>
                    </div>
                    <DialogFooter className="shrink-0 border-t bg-background pt-4"><DialogClose asChild><Button type="button" variant="outline">Cancel</Button></DialogClose><Button type="submit" disabled={processing||!cycleId||(academic.disciplines.length>0&&!disciplineId)}>{processing&&<Spinner/>}{application?'Save Draft':'Create Draft'}</Button></DialogFooter>
                </>;
                }}
            </Form>
        </DialogContent>
    </Dialog>;
}

function ConfirmAction({label,title,description,action,method='patch',icon}:{label:string;title:string;description:string;action:string;method?:'patch'|'post';icon:'submit'|'withdraw'}){
    return <Dialog><DialogTrigger asChild><Button size="sm" variant="outline">{icon==='submit'?<Send/>:<XCircle/>}{label}</Button></DialogTrigger><DialogContent className="w-[calc(100vw-2rem)] sm:!max-w-lg"><DialogHeader><DialogTitle>{title}</DialogTitle><DialogDescription>{description}</DialogDescription></DialogHeader><Form action={action} method={method}>{({processing,errors})=><><div className="space-y-2">{Object.values(errors).filter(Boolean).map((message,index)=><div key={index} className="rounded-md border border-destructive/40 bg-destructive/5 px-3 py-2 text-sm text-destructive">{String(message)}</div>)}</div><DialogFooter><DialogClose asChild><Button type="button" variant="outline">Cancel</Button></DialogClose><Button type="submit" variant="outline" disabled={processing}>{processing&&<Spinner/>}{icon==='submit'?<Send/>:<XCircle/>}{label}</Button></DialogFooter></>}</Form></DialogContent></Dialog>;
}

function IneligibleDialog({collegeId,choice}:{collegeId:number;choice:Choice}){
    return <Dialog><DialogTrigger asChild><Button size="sm" variant="outline"><UserX/>Mark Ineligible</Button></DialogTrigger><DialogContent className="w-[calc(100vw-2rem)] sm:!max-w-lg"><DialogHeader><DialogTitle>Mark Candidate Ineligible</DialogTitle><DialogDescription>This is preliminary/basic eligibility for this program choice. Selection-rule score thresholds are evaluated later by Score / Merit processing.</DialogDescription></DialogHeader><Form action={`/college/${collegeId}/admission-application-choices/${choice.id}/eligibility`} method="patch">{({processing,errors})=><><input type="hidden" name="eligibility_status" value="INELIGIBLE"/><div className="space-y-2"><Label>Reason</Label><textarea name="eligibility_reason" defaultValue={choice.eligibility_reason??''} className="min-h-24 w-full rounded-md border bg-background px-3 py-2 text-sm" placeholder="Why this candidate is not eligible for this program choice"/>{errors.eligibility_reason&&<p className="text-xs text-destructive">{errors.eligibility_reason}</p>}</div><DialogFooter><DialogClose asChild><Button type="button" variant="outline">Cancel</Button></DialogClose><Button type="submit" variant="outline" disabled={processing}>{processing&&<Spinner/>}<UserX/>Mark Ineligible</Button></DialogFooter></>}</Form></DialogContent></Dialog>;
}

function EligibilityActions({collegeId,choice}:{collegeId:number;choice:Choice}){
    return <div className="flex flex-wrap gap-2">
        <Form action={`/college/${collegeId}/admission-application-choices/${choice.id}/eligibility`} method="patch">{({processing})=><><input type="hidden" name="eligibility_status" value="ELIGIBLE"/><Button type="submit" size="sm" variant="outline" disabled={processing||choice.eligibility_status==='ELIGIBLE'}>{processing?<Spinner/>:<UserCheck/>}Mark Eligible</Button></>}</Form>
        <IneligibleDialog collegeId={collegeId} choice={choice}/>
        {choice.eligibility_status!=='PENDING'&&<Form action={`/college/${collegeId}/admission-application-choices/${choice.id}/eligibility`} method="patch">{({processing})=><><input type="hidden" name="eligibility_status" value="PENDING"/><Button type="submit" size="sm" variant="ghost" disabled={processing}>{processing?<Spinner/>:<Undo2/>}Reset Pending</Button></>}</Form>}
    </div>;
}

function statusClass(status:string){if(status==='SUBMITTED'||status==='ELIGIBLE')return 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-300';if(status==='INELIGIBLE'||status==='WITHDRAWN')return 'border-destructive/30 bg-destructive/5 text-destructive';return 'border-border bg-muted text-muted-foreground';}

export default function AdmissionApplications({college,cycles,selectionContexts,directSelectionContexts,formConfigs,academicOptions,applications,filters,can}:Props){
    const [search,setSearch]=useState(filters.search??'');
    const activeCycles=cycles.filter(c=>c.status==='ACTIVE'&&c.college_program_offering_id);
    const submitSearch=(e:FormEvent)=>{e.preventDefault();router.get(`/college/${college.id}/admission-applications`,search.trim()?{search:search.trim()}:{},{preserveState:true,replace:true});};
    return <><Head title="Applications / Candidate Eligibility"/><div className="space-y-6 p-4 md:p-6">
        <header className="flex flex-wrap items-end justify-between gap-3 border-b pb-5"><div><p className="text-sm font-medium text-primary">{college.code} · Student Admission Processing</p><h1 className="text-3xl font-semibold">Applications / Candidate Eligibility</h1><p className="max-w-4xl text-sm text-muted-foreground">Capture the same configured Admission Form and curriculum choices used by the public Applicant Portal. Applications are not seat-capacity gated; reservation, roster and seat allocation are handled later in the admission workflow.</p></div>{can.create&&college.status==='ACTIVE'&&activeCycles.length>0&&<ApplicationForm collegeId={college.id} cycles={cycles} formConfigs={formConfigs} academicOptions={academicOptions}/>}</header>

        {activeCycles.length===0&&<Card><CardContent className="space-y-1 p-4 text-sm"><div className="font-medium">Application entry is not ready yet.</div><p className="text-muted-foreground">Create and activate an Admission Cycle linked to an ACTIVE Program Offering first.</p></CardContent></Card>}

        <Card><CardContent className="p-4"><form onSubmit={submitSearch} className="flex flex-wrap gap-2"><div className="relative min-w-[240px] flex-1"><Search className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"/><Input value={search} onChange={e=>setSearch(e.target.value)} className="pl-9" placeholder="Search application no, candidate, email, phone or external reference"/></div><Button type="submit" variant="outline"><Search/>Search</Button>{filters.search&&<Button type="button" variant="ghost" onClick={()=>{setSearch('');router.get(`/college/${college.id}/admission-applications`,{}, {preserveState:true,replace:true});}}>Clear</Button>}</form></CardContent></Card>

        {applications.data.length===0?<Card><CardContent className="grid place-items-center py-16 text-center"><ClipboardCheck className="size-10 text-muted-foreground"/><h2 className="mt-3 font-semibold">No Admission Application found</h2><p className="mt-1 text-sm text-muted-foreground">Create a draft when an Admission Cycle and Program Offering are active.</p></CardContent></Card>:<div className="space-y-4">{applications.data.map(application=><Card key={application.id}><CardHeader className="gap-3"><div className="flex flex-wrap items-start justify-between gap-3"><div className="min-w-0"><div className="flex flex-wrap items-center gap-2"><CardTitle>{application.candidate_name}</CardTitle><span className={`rounded-full border px-2 py-0.5 text-xs font-medium ${statusClass(application.status)}`}>{application.status}</span><span className="rounded-full border px-2 py-0.5 text-xs font-medium">{application.admission_mode==='DIRECT'?'DIRECT ADMISSION':'REGULAR / SELECTION'}</span><span className="rounded-full border px-2 py-0.5 text-xs font-medium">{application.entry_source??'INTERNAL'}</span></div><p className="mt-1 text-sm text-muted-foreground">{application.application_no} · {application.admission_cycle.name} · {application.admission_cycle.program_offering?.program_template.name??'Program Offering'} · {application.admission_cycle.academic_session.name}</p><p className="mt-1 text-xs text-muted-foreground">DOB {application.date_of_birth?.slice(0,10)}{application.email?` · ${application.email}`:''}{application.phone?` · ${application.phone}`:''}{application.external_reference?` · Ref ${application.external_reference}`:''}</p></div><div className="flex flex-wrap gap-2">{application.status==='DRAFT'&&can.update&&<ApplicationForm collegeId={college.id} cycles={cycles} formConfigs={formConfigs} academicOptions={academicOptions} application={application}/>} {application.status==='DRAFT'&&can.submit&&<ConfirmAction label="Submit" title="Submit Admission Application?" description={application.admission_mode==='DIRECT'?'Submit this Direct Admission application. Academic choices and the configured form are locked for this application; seat allocation remains downstream.':'Submit this application with its academic choices and configured form data. Reservation, merit/roster and seat allocation remain downstream.'} action={`/college/${college.id}/admission-applications/${application.id}/submit`} icon="submit"/>}{application.status!=='WITHDRAWN'&&can.withdraw&&<ConfirmAction label="Withdraw" title="Withdraw Admission Application?" description="Use this only when the candidate/application is being withdrawn. Future downstream Admission records will block withdrawal once processing has progressed." action={`/college/${college.id}/admission-applications/${application.id}/withdraw`} icon="withdraw"/>}</div></div></CardHeader><CardContent className="space-y-3">
                {application.submitted_at&&<p className="text-xs text-muted-foreground">Submitted at: {new Date(application.submitted_at).toLocaleString()}</p>}
                {application.status==='SUBMITTED'&&application.admission_mode==='REGULAR'&&application.choices.length===0&&<div className="rounded-md border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200"><div className="font-medium">Eligibility processing context is not linked on this older submission.</div><p className="mt-1 text-xs">This record was submitted before automatic Regular Admission linking. During QA, withdraw/clean this test application and submit it again after applying the fix. New Regular submissions automatically lock the matching Intake + ACTIVE Selection Rule without exposing seat selection to the applicant.</p></div>}
                {application.status==='SUBMITTED'&&application.admission_mode==='DIRECT'&&application.choices.length===0&&<div className="rounded-md border bg-muted/20 p-3 text-sm"><div className="font-medium">Direct Admission route</div><p className="mt-1 text-xs text-muted-foreground">Eligibility / Selection Rule processing is intentionally bypassed for this application. It continues through the authorized Direct Admission workflow.</p></div>}
                <div className="space-y-3">{application.choices.map(choice=><div key={choice.id} className="rounded-md border p-3"><div className="flex flex-wrap items-start justify-between gap-3"><div className="min-w-0"><div className="font-medium">Preference {choice.preference_no} · {choice.intake.offering.program_template.name}</div><div className="mt-1 text-sm text-muted-foreground">{choice.bucket_label} · {choice.basis_capacity} seats · {choice.reservation_plan?'Reservation ACTIVE':'Open/General'}</div>{choice.selection_rule?<div className="mt-1 text-xs text-muted-foreground">Rule: {choice.selection_rule.code} v{choice.selection_rule.version_no} · {choice.selection_rule.selection_mode} · M {choice.selection_rule.merit_weight_percent}% / E {choice.selection_rule.entrance_weight_percent}% / I {choice.selection_rule.interview_weight_percent}%</div>:<div className="mt-1 text-xs text-muted-foreground">Direct Admission · no Selection Rule locked</div>}</div><span className={`rounded-full border px-2 py-0.5 text-xs font-medium ${statusClass(choice.eligibility_status)}`}>{choice.eligibility_status}</span></div>{choice.eligibility_reason&&<p className="mt-2 text-sm text-muted-foreground"><span className="font-medium text-foreground">Eligibility note:</span> {choice.eligibility_reason}</p>}<div className="mt-3 rounded-md border bg-muted/20 p-3"><div className="flex flex-wrap items-center justify-between gap-3"><div><div className="text-sm font-medium">Eligibility Review</div><p className="mt-0.5 text-xs text-muted-foreground">{application.status==='DRAFT'?'Submit this application first. Eligibility is reviewed only after the Regular application is submitted and its processing context is locked.':'Review this submitted Regular application before Score / Entrance / Interview processing.'}</p></div>{application.status==='SUBMITTED'&&can.eligibility?<EligibilityActions collegeId={college.id} choice={choice}/>:application.status==='DRAFT'?<div className="flex flex-wrap gap-2"><Button type="button" size="sm" variant="outline" disabled><UserCheck/>Mark Eligible</Button><Button type="button" size="sm" variant="outline" disabled><UserX/>Mark Ineligible</Button></div>:null}</div></div></div>)}</div>
                {application.remarks&&<p className="text-sm text-muted-foreground"><span className="font-medium text-foreground">Remarks:</span> {application.remarks}</p>}
            </CardContent></Card>)}</div>}

        {applications.last_page>1&&<div className="flex items-center justify-between"><p className="text-sm text-muted-foreground">Page {applications.current_page} of {applications.last_page} · {applications.total} applications</p><div className="flex gap-2">{applications.prev_page_url?<Link href={applications.prev_page_url}><Button variant="outline" size="sm"><ChevronLeft/>Previous</Button></Link>:<Button variant="outline" size="sm" disabled><ChevronLeft/>Previous</Button>}{applications.next_page_url?<Link href={applications.next_page_url}><Button variant="outline" size="sm">Next<ChevronRight/></Button></Link>:<Button variant="outline" size="sm" disabled>Next<ChevronRight/></Button>}</div></div>}
    </div></>;
}
