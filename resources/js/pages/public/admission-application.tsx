import { Form, Head, router } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, BookOpenCheck, Check, CheckCircle2, Clock3, FileCheck2, GraduationCap, LogOut, Mail, MessageSquareText, Phone, ShieldCheck, Sparkles, UserRoundCheck } from 'lucide-react';
import { useEffect, useMemo, useState, type FormEvent, type ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { DatePicker } from '@/components/ui/date-picker';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { admissionFieldValidationError, admissionFieldValidationHint } from '@/lib/admission-field-validation';

type Option={value:string;label:string};
type Condition={source_field_id:number;operator:string;compare_values:string[]};
type Field={id:number;field_key:string;label:string;field_type:string;system_purpose?:string|null;placeholder?:string|null;help_text?:string|null;is_required:boolean;college_admission_form_panel_id?:number|null;options?:Option[];conditions?:Condition[];condition_match_mode?:'ALL'|'ANY';validation_rules?:Record<string,unknown>|null;comparison_rule?:{source_field_id:number;source_field_label?:string|null;operator:string}|null;copy_rule?:{source_field_id:number;source_field_label?:string|null;trigger_field_id:number;trigger_field_label?:string|null;trigger_values:string[];read_only:boolean}|null};
type Panel={id:number;title:string;code:string;description?:string|null;display_order:number};
type Step={id:number;title:string;code:string;description?:string|null;panels?:Panel[];fields?:Field[]};
type AcademicMapping={id:number;course_id:number;discipline_id:number|null;specialization_id:number|null;course_name:string;course_code:string;source_discipline_id?:number|null;source_discipline_name?:string|null;source_discipline_code?:string|null};
type AcademicSlot={id:number;name:string;selection_mode:'MANDATORY'|'CHOICE';min_selection:number|null;max_selection:number|null;credits?:string|null;category_name?:string|null;type_name?:string|null;mappings:AcademicMapping[]};
type AcademicTerm={id:number;name:string;sequence_no:number;slots:AcademicSlot[]};
type AcademicDiscipline={id:number;name:string;code:string;specialization_required?:boolean;specializations:{id:number;name:string;code:string}[]};
type AcademicOptions={curriculum:{id:number;name:string;code:string;version:string}|null;disciplines:AcademicDiscipline[];terms:AcademicTerm[]};
type PublicForm={slug:string;step_display_mode?:'SAME_WINDOW'|'NEW_WINDOW';seat_selection_required:boolean;college:{id:number;name:string;code:string};cycle:{id:number;name:string;code:string;application_start_date:string;application_end_date:string;status:string};program:{name:string;code:string;session?:string|null};template:{id:number;name:string;code:string;steps?:Step[]}|null;fee:{required:boolean;amount:string;currency:string};choices:unknown[];academic_options:AcademicOptions;availability:{can_submit:boolean;state:string;message:string;opens_on?:string;closes_on?:string};help?:{phone?:string|null;email?:string|null;description?:string|null}};
type Props={publicForm:PublicForm;applicant:{name:string;email:string;phone?:string|null;date_of_birth?:string|null;registration_no?:string|null;registration_no_preview?:string|null};successApplicationNo?:string|null};

type ChoiceSourcePackage={key:string;name:string;bySlot:Record<number,AcademicMapping[]>};
type ChoiceCategory={name:string;slots:AcademicSlot[];packageMode:boolean;packages:ChoiceSourcePackage[]};
const sourceKey=(mapping:AcademicMapping)=>mapping.source_discipline_id?`discipline:${mapping.source_discipline_id}`:'common';
const sourceName=(mapping:AcademicMapping)=>mapping.source_discipline_name??'Common / Interdisciplinary';
function buildChoiceCategories(terms:AcademicTerm[]):ChoiceCategory[]{
    const grouped=new Map<string,AcademicSlot[]>();
    safe(terms).forEach(term=>safe(term.slots).forEach(slot=>{
        const key=slot.category_name??'Choice / Elective';
        grouped.set(key,[...(grouped.get(key)??[]),{...slot,mappings:safe(slot.mappings)}]);
    }));
    return Array.from(grouped.entries()).map(([name,slots])=>{
        const keys=Array.from(new Set(slots.flatMap(slot=>safe(slot.mappings).map(sourceKey))));
        const packages=keys.map(key=>{
            const bySlot:Record<number,AcademicMapping[]>={};
            let valid=true;
            let label='Common / Interdisciplinary';
            for(const slot of slots){
                const min=slot.min_selection??1;
                const max=slot.max_selection??min;
                const mappings=safe(slot.mappings).filter(m=>sourceKey(m)===key);
                if(min!==max||mappings.length!==min||mappings.length===0){valid=false;break;}
                bySlot[slot.id]=mappings;
                if(mappings[0])label=sourceName(mappings[0]);
            }
            return valid?{key,name:label,bySlot}:null;
        }).filter(Boolean) as ChoiceSourcePackage[];
        return {name,slots,packageMode:keys.length>0&&packages.length===keys.length,packages};
    });
}
const sameIds=(a:number[],b:number[])=>[...a].sort((x,y)=>x-y).join(',')===[...b].sort((x,y)=>x-y).join(',');


const selectClass='h-11 w-full rounded-xl border bg-background px-3 text-sm shadow-xs outline-none transition focus:border-primary/60 focus:ring-[3px] focus:ring-primary/10';

function HelpCard({help,className=''}:{help:PublicForm['help'];className?:string}){
    const phone=help?.phone?.trim();
    const email=help?.email?.trim();
    const description=help?.description?.trim()||'For application assistance, contact the admission office using the details provided here.';
    return <Card className={`overflow-hidden rounded-2xl border-primary/15 bg-background/95 shadow-sm ${className}`}>
        <CardContent className="p-4 md:p-5">
            <div className="grid gap-4 lg:grid-cols-[minmax(190px,0.75fr)_minmax(0,1.8fr)] lg:items-stretch">
                <div className="flex items-start gap-3">
                    <div className="grid size-10 shrink-0 place-items-center rounded-xl border border-primary/15 bg-primary/10"><MessageSquareText className="size-5 text-primary"/></div>
                    <div>
                        <div className="text-xs font-semibold uppercase tracking-[0.16em] text-primary">Need Help?</div>
                        <p className="mt-1.5 text-sm leading-5 text-muted-foreground">We&apos;re here to help with your application.</p>
                    </div>
                </div>
                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    {phone&&<a href={`tel:${phone}`} className="group flex min-h-[72px] items-center gap-3 rounded-xl border border-primary/10 bg-primary/[0.025] px-3.5 py-3 transition hover:border-primary/25 hover:bg-primary/[0.055]"><div className="grid size-9 shrink-0 place-items-center rounded-lg bg-primary/10"><Phone className="size-4 text-primary"/></div><div className="min-w-0"><div className="text-[10px] font-semibold uppercase tracking-[0.14em] text-muted-foreground">Call us</div><div className="mt-0.5 truncate text-sm font-semibold text-foreground">{phone}</div></div></a>}
                    {email&&<a href={`mailto:${email}`} className="group flex min-h-[72px] items-center gap-3 rounded-xl border border-primary/10 bg-primary/[0.025] px-3.5 py-3 transition hover:border-primary/25 hover:bg-primary/[0.055]"><div className="grid size-9 shrink-0 place-items-center rounded-lg bg-primary/10"><Mail className="size-4 text-primary"/></div><div className="min-w-0"><div className="text-[10px] font-semibold uppercase tracking-[0.14em] text-muted-foreground">Email us</div><div className="mt-0.5 break-all text-sm font-semibold text-foreground">{email}</div></div></a>}
                    <div className="min-h-[72px] rounded-xl border border-primary/10 bg-muted/[0.16] px-3.5 py-3 sm:col-span-2 xl:col-span-1"><div className="text-[10px] font-semibold uppercase tracking-[0.14em] text-muted-foreground">Help &amp; instructions</div><p className="mt-1 whitespace-pre-line text-sm leading-5 text-foreground/80">{description}</p></div>
                </div>
            </div>
        </CardContent>
    </Card>;
}
const errorFor=(errors:Record<string,string>,key:string)=>errors[key]??errors[key.replaceAll('[','.').replaceAll(']','')];
const safe=<T,>(value:T[]|undefined|null)=>Array.isArray(value)?value:[];

const canonicalConditionValue=(value:unknown)=>String(value??'').trim().toLowerCase().replace(/[^a-z0-9]+/g,'_').replace(/^_+|_+$/g,'');
function conditionMatches(actual:unknown,operator:string,expected:string[]){
    const blank=actual===null||actual===undefined||actual===''||(Array.isArray(actual)&&actual.length===0);
    if(operator==='IS_EMPTY')return blank;if(operator==='IS_NOT_EMPTY')return !blank;
    const actualValues=Array.isArray(actual)?actual.map(String):[String(actual??'')];
    const actualCanonical=actualValues.map(canonicalConditionValue);const expectedCanonical=expected.map(canonicalConditionValue);
    const overlap=actualCanonical.some(v=>expectedCanonical.includes(v));
    if(operator==='EQUALS'||operator==='IN')return overlap;if(operator==='NOT_EQUALS'||operator==='NOT_IN')return !overlap;
    if(operator==='CONTAINS')return actualValues.some(v=>expected.some(e=>e&&v.toLowerCase().includes(String(e).toLowerCase())));return false;
}
function fieldVisible(field:Field,values:Record<number,unknown>){const conditions=safe(field.conditions);if(!conditions.length)return true;const results=conditions.map(c=>conditionMatches(values[c.source_field_id],c.operator,c.compare_values??[]));return field.condition_match_mode==='ANY'?results.some(Boolean):results.every(Boolean);}

function textInputConstraint(mode:unknown,value:string){
    if(!value||!mode||mode==='ANY')return '';
    if(mode==='LETTERS_ONLY'&&!/^[\p{L}\p{M}]+(?:[ '\-][\p{L}\p{M}]+)*$/u.test(value))return 'Use letters only.';
    if(mode==='DIGITS_ONLY'&&!/^\d+$/u.test(value))return 'Use digits only.';
    if(mode==='ALPHANUMERIC'&&!/^[\p{L}\p{M}\p{N}]+$/u.test(value))return 'Use letters and numbers only.';
    return '';
}

function DynamicField({field,values,setValue,errors}:{field:Field;values:Record<number,unknown>;setValue:(id:number,value:unknown)=>void;errors:Record<string,string>}){
    const options=safe(field.options);if(!fieldVisible(field,values))return null;
    const name=`custom_fields[${field.id}]`;const error=errorFor(errors,`custom_fields.${field.id}`);const currentValue=values[field.id];const clientError=(currentValue===undefined||currentValue===null||currentValue===''||(Array.isArray(currentValue)&&currentValue.length===0))?null:admissionFieldValidationError(field,currentValue,values);const displayError=error??clientError??undefined;const validationHint=admissionFieldValidationHint(field);const copyLocked=Boolean(field.copy_rule?.read_only&&conditionMatches(values[field.copy_rule.trigger_field_id],'IN',field.copy_rule.trigger_values??[]));const vr=field.validation_rules??{};const exactLength=Number(vr.exact_length??0)||undefined;const minLength=exactLength??(Number(vr.min_length??0)||undefined);const maxLength=exactLength??(Number(vr.max_length??0)||undefined);const textMode=String(vr.text_input_mode??'ANY');const textInputMode=textMode==='DIGITS_ONLY'?'numeric':undefined;const validateTextInput=(element:HTMLInputElement|HTMLTextAreaElement,value:string)=>element.setCustomValidity(textInputConstraint(textMode,value));
    const common=<><Label htmlFor={`field-${field.id}`} className="text-sm font-medium">{field.label}{field.is_required&&<span className="text-destructive"> *</span>}</Label>{field.help_text&&<p className="text-xs leading-5 text-muted-foreground">{field.help_text}</p>}</>;
    if(field.field_type==='SELECT')return <div className="space-y-2">{common}<select id={`field-${field.id}`} name={name} className={selectClass} required={field.is_required} disabled={copyLocked} value={String(values[field.id]??'')} onChange={e=>setValue(field.id,e.target.value)}><option value="">{field.placeholder??`Select ${field.label}`}</option>{options.map(o=><option key={o.value} value={o.value}>{o.label}</option>)}</select>{validationHint&&<p className="text-[11px] text-muted-foreground">{validationHint}</p>}{displayError&&<p className="text-xs text-destructive">{displayError}</p>}</div>;
    if(field.field_type==='RADIO'||field.field_type==='YES_NO')return <div className="space-y-2">{common}<div className="flex flex-wrap gap-3">{options.map(o=><label key={o.value} className={`flex cursor-pointer items-center gap-2 rounded-xl border px-3 py-2 text-sm transition ${String(values[field.id]??'')===o.value?'border-primary/50 bg-primary/5':'bg-background hover:bg-muted/30'}`}><input type="radio" name={name} value={o.value} required={field.is_required} disabled={copyLocked} checked={String(values[field.id]??'')===o.value} onChange={()=>setValue(field.id,o.value)}/>{o.label}</label>)}</div>{validationHint&&<p className="text-[11px] text-muted-foreground">{validationHint}</p>}{displayError&&<p className="text-xs text-destructive">{displayError}</p>}</div>;
    if(field.field_type==='CHECKBOX'||field.field_type==='MULTISELECT')return <div className="space-y-2">{common}<div className="grid gap-2 sm:grid-cols-2">{options.map(o=>{const selected=Array.isArray(values[field.id])?(values[field.id] as unknown[]).map(String):[];return <label key={o.value} className={`flex cursor-pointer items-center gap-2 rounded-xl border px-3 py-2 text-sm ${selected.includes(o.value)?'border-primary/50 bg-primary/5':'bg-background'}`}><input type="checkbox" name={`${name}[]`} value={o.value} checked={selected.includes(o.value)} disabled={copyLocked} onChange={e=>setValue(field.id,e.target.checked?[...selected,o.value]:selected.filter(v=>v!==o.value))}/>{o.label}</label>})}</div>{validationHint&&<p className="text-[11px] text-muted-foreground">{validationHint}</p>}{displayError&&<p className="text-xs text-destructive">{displayError}</p>}</div>;
    if(field.field_type==='FILE'||field.field_type==='IMAGE'){
        const selectedFile=currentValue instanceof File?currentValue:null;
        const extensions=Array.isArray(vr.extensions)?(vr.extensions as unknown[]).map(value=>String(value).trim().toLowerCase().replace(/^\./,'')).filter(Boolean):[];
        const accept=field.field_type==='IMAGE'&&!extensions.length?'image/*':extensions.length?extensions.map(ext=>`.${ext}`).join(','):undefined;
        const maxKb=Number(vr.max_kb??0)||undefined;
        return <div className="space-y-2">{common}<Input id={`field-${field.id}`} type="file" name={name} required={field.is_required} accept={accept} onChange={e=>{const file=e.currentTarget.files?.[0]??null;setValue(field.id,file);e.currentTarget.setCustomValidity('');}} className="h-11 rounded-xl"/>{selectedFile&&<div className="rounded-xl border border-primary/15 bg-primary/[0.035] px-3 py-2 text-xs"><div className="font-medium text-foreground">{selectedFile.name}</div><div className="mt-0.5 text-muted-foreground">{Math.max(1,Math.round(selectedFile.size/1024)).toLocaleString()} KB selected{maxKb?` · limit ${maxKb.toLocaleString()} KB`:''}</div></div>}{validationHint&&<p className="text-[11px] text-muted-foreground">{validationHint}</p>}{displayError&&<p className="text-xs text-destructive">{displayError}</p>}</div>;
    }
    if(field.field_type==='TEXTAREA')return <div className="space-y-2">{common}<textarea id={`field-${field.id}`} name={name} required={field.is_required} disabled={copyLocked} minLength={minLength} maxLength={maxLength} placeholder={field.placeholder??''} value={String(values[field.id]??'')} onChange={e=>{validateTextInput(e.currentTarget,e.target.value);setValue(field.id,e.target.value)}} onInvalid={e=>validateTextInput(e.currentTarget,String(values[field.id]??''))} className="min-h-28 w-full rounded-xl border bg-background px-3 py-2 text-sm outline-none focus:border-primary/60 focus:ring-[3px] focus:ring-primary/10"/>{validationHint&&<p className="text-[11px] text-muted-foreground">{validationHint}</p>}{displayError&&<p className="text-xs text-destructive">{displayError}</p>}</div>;
    if(field.field_type==='DATE')return <div className="space-y-2">{common}<DatePicker id={`field-${field.id}`} name={name} value={String(values[field.id]??'')} onValueChange={value=>setValue(field.id,value)} invalid={Boolean(error)}/>{validationHint&&<p className="text-[11px] text-muted-foreground">{validationHint}</p>}{displayError&&<p className="text-xs text-destructive">{displayError}</p>}</div>;
    const inputType=field.field_type==='NUMBER'?'number':field.field_type==='EMAIL'?'email':field.field_type==='PHONE'?'tel':'text';
    return <div className="space-y-2">{common}<Input id={`field-${field.id}`} type={inputType} name={name} required={field.is_required} disabled={copyLocked} minLength={minLength} maxLength={maxLength} min={field.field_type==='NUMBER'&&vr.min_value!==undefined?Number(vr.min_value):undefined} max={field.field_type==='NUMBER'&&vr.max_value!==undefined?Number(vr.max_value):undefined} step={field.field_type==='NUMBER'?(vr.integer_only?'1':vr.decimal_places!==undefined?String(1/Math.pow(10,Number(vr.decimal_places))):'any'):undefined} placeholder={field.placeholder??''} inputMode={field.field_type==='TEXT'?textInputMode:undefined} value={String(values[field.id]??'')} onChange={e=>{if(field.field_type==='TEXT')validateTextInput(e.currentTarget,e.target.value);setValue(field.id,e.target.value)}} onInvalid={e=>{if(field.field_type==='TEXT')validateTextInput(e.currentTarget,String(values[field.id]??''))}} className="h-11 rounded-xl"/>{validationHint&&<p className="text-[11px] text-muted-foreground">{validationHint}</p>}{displayError&&<p className="text-xs text-destructive">{displayError}</p>}</div>;
}

function PublicAdmissionApplication({publicForm,applicant,successApplicationNo}:Props){
    const [values,setValues]=useState<Record<number,unknown>>({});const setValue=(id:number,value:unknown)=>setValues(prev=>({...prev,[id]:value}));
    const steps=safe(publicForm.template?.steps).map(step=>({...step,panels:safe(step.panels),fields:safe(step.fields).map(field=>({...field,options:safe(field.options),conditions:safe(field.conditions)}))}));
    useEffect(()=>{setValues(prev=>{let changed=false;const next={...prev};steps.flatMap(step=>safe(step.fields)).forEach(field=>{const rule=field.copy_rule;if(!rule)return;const active=conditionMatches(next[rule.trigger_field_id],'IN',rule.trigger_values??[]);if(!active)return;const source=next[rule.source_field_id]??'';if(String(next[field.id]??'')!==String(source??'')){next[field.id]=source;changed=true;}});return changed?next:prev;});},[values]);
    const academic=publicForm.academic_options??{curriculum:null,disciplines:[],terms:[]};
    const disciplines=safe(academic.disciplines);const defaultDiscipline=disciplines.length===1?String(disciplines[0].id):'';
    const [disciplineId,setDisciplineId]=useState(defaultDiscipline);const selectedDiscipline=disciplines.find(d=>String(d.id)===disciplineId);
    const specializations=safe(selectedDiscipline?.specializations);const [specializationId,setSpecializationId]=useState('');
    const [courseChoices,setCourseChoices]=useState<Record<number,number[]>>({});
    const selectDiscipline=(value:string)=>{setDisciplineId(value);const d=disciplines.find(item=>String(item.id)===value);const specs=safe(d?.specializations);setSpecializationId('');setCourseChoices({});};
    const selectSpecialization=(value:string)=>{setSpecializationId(value);setCourseChoices({});};
    const applicableMappings=(slot:AcademicSlot)=>safe(slot.mappings).filter(m=>m.discipline_id===null?(m.specialization_id===null):(String(m.discipline_id)===disciplineId&&(m.specialization_id===null||String(m.specialization_id)===specializationId)));
    const academicTerms=useMemo(()=>safe(academic.terms).map(term=>({...term,slots:safe(term.slots).map(slot=>({...slot,mappings:applicableMappings(slot)})).filter(slot=>safe(slot.mappings).length>0)})).filter(term=>safe(term.slots).length>0),[academic,disciplineId,specializationId]);
    const choiceTerms=useMemo(()=>academicTerms.map(term=>({...term,slots:safe(term.slots).filter(slot=>slot.selection_mode==='CHOICE')})).filter(term=>term.slots.length>0),[academicTerms]);
    const choiceCategories=useMemo(()=>buildChoiceCategories(choiceTerms),[choiceTerms]);
    const mandatoryCourseCount=useMemo(()=>academicTerms.reduce((count,term)=>count+safe(term.slots).filter(slot=>slot.selection_mode==='MANDATORY').reduce((sum,slot)=>sum+safe(slot.mappings).length,0),0),[academicTerms]);
    const selectPackage=(category:ChoiceCategory,pkg:ChoiceSourcePackage)=>setCourseChoices(current=>{const next={...current};safe(category.slots).forEach(slot=>{next[slot.id]=safe(pkg.bySlot[slot.id]).map(m=>m.id);});return next;});
    const packageSelected=(category:ChoiceCategory,pkg:ChoiceSourcePackage)=>safe(category.slots).every(slot=>sameIds(courseChoices[slot.id]??[],safe(pkg.bySlot[slot.id]).map(m=>m.id)));
    const toggleChoice=(slot:AcademicSlot,mappingId:number)=>setCourseChoices(current=>{const selected=current[slot.id]??[];const max=slot.max_selection??slot.min_selection??1;if(selected.includes(mappingId))return {...current,[slot.id]:selected.filter(id=>id!==mappingId)};if(selected.length>=max)return current;return {...current,[slot.id]:[...selected,mappingId]};});
    const stepByStep=publicForm.step_display_mode==='NEW_WINDOW';
    const pageTitles=['Academic Selection',...steps.map(s=>s.title),'Review & Submit'];
    const totalPages=pageTitles.length;
    const [activePage,setActivePage]=useState(0);
    const [highestUnlockedPage,setHighestUnlockedPage]=useState(0);
    const dateLabel=(value?:string)=>value?new Date(`${value}T00:00:00`).toLocaleDateString():'';
    const validatePage=(pageIndex:number)=>{if(!stepByStep)return true;const page=document.querySelector(`[data-public-form-page="${pageIndex}"]`);if(!page)return true;if(pageIndex>=1&&pageIndex<=steps.length){for(const field of safe(steps[pageIndex-1]?.fields)){if(!fieldVisible(field,values))continue;const value=values[field.id];const message=admissionFieldValidationError(field,value,values);if(message){const control=page.querySelector(`[name="custom_fields[${field.id}]"]`) as HTMLInputElement|HTMLSelectElement|HTMLTextAreaElement|null;if(control){control.setCustomValidity(message);control.reportValidity();control.focus();setTimeout(()=>control.setCustomValidity(''),0);}return false;}}}for(const control of Array.from(page.querySelectorAll('input, select, textarea')) as Array<HTMLInputElement|HTMLSelectElement|HTMLTextAreaElement>){if(!control.checkValidity()){control.reportValidity();return false;}}return true;};
    const validateCurrentPage=()=>validatePage(activePage);
    const nextPage=()=>{if(!validateCurrentPage())return;const next=Math.min(activePage+1,totalPages-1);setHighestUnlockedPage(current=>Math.max(current,next));setActivePage(next);};
    const previousPage=()=>setActivePage(page=>Math.max(page-1,0));
    const goToPage=(index:number)=>{if(index<=highestUnlockedPage)setActivePage(index);};
    const pageForServerError=(key:string)=>{
        if(key.startsWith('academic_preference.'))return 0;
        if(key==='preview_confirmed')return totalPages-1;
        const customMatch=key.match(/^custom_fields[.\[]?(\d+)/);
        if(customMatch){
            const fieldId=Number(customMatch[1]);
            const stepIndex=steps.findIndex(step=>safe(step.fields).some(field=>field.id===fieldId));
            if(stepIndex>=0)return stepIndex+1;
        }
        return activePage;
    };
    const handleSubmitError=(serverErrors:Record<string,string>)=>{
        if(!stepByStep)return;
        const firstKey=Object.keys(serverErrors)[0];
        if(!firstKey)return;
        const page=pageForServerError(firstKey);
        setHighestUnlockedPage(current=>Math.max(current,page));
        setActivePage(page);
        requestAnimationFrame(()=>document.querySelector(`[data-public-form-page=\"${page}\"]`)?.scrollIntoView({behavior:'smooth',block:'start'}));
    };
    const handleFormSubmit=(event:FormEvent<HTMLFormElement>)=>{
        if(stepByStep&&!validateCurrentPage())event.preventDefault();
    };
    const logout=()=>router.post(`/apply/${publicForm.slug}/logout`,{}, {preserveScroll:false});
    const dynamicSummary=steps.flatMap(step=>safe(step.fields).map(field=>{if(field.system_purpose==='CANDIDATE_PROFILE_PHOTO')return null;const value=values[field.id];const display=value instanceof File?value.name:Array.isArray(value)?value.join(', '):String(value??'');return display?{label:field.label,value:display,step:step.title}:null}).filter(Boolean) as {label:string;value:string;step:string}[]);
    const profilePhotoField=steps.flatMap(step=>safe(step.fields)).find(field=>field.system_purpose==='CANDIDATE_PROFILE_PHOTO');
    const profilePhotoFile=profilePhotoField&&values[profilePhotoField.id] instanceof File?values[profilePhotoField.id] as File:null;
    const [profilePhotoPreview,setProfilePhotoPreview]=useState<string|null>(null);
    useEffect(()=>{if(!profilePhotoFile){setProfilePhotoPreview(null);return;}const url=URL.createObjectURL(profilePhotoFile);setProfilePhotoPreview(url);return()=>URL.revokeObjectURL(url);},[profilePhotoFile]);

    if(successApplicationNo)return <><Head title="Application submitted"/><main className="min-h-screen bg-[radial-gradient(circle_at_top,hsl(var(--primary)/0.10),transparent_34%)] p-4"><div className="mx-auto grid min-h-[90vh] max-w-3xl place-items-center"><Card className="w-full overflow-hidden rounded-3xl border-primary/15 shadow-xl"><div className="h-1.5 bg-primary"/><CardContent className="py-14 text-center"><div className="mx-auto grid size-16 place-items-center rounded-2xl bg-primary/10"><CheckCircle2 className="size-9 text-primary"/></div><h1 className="mt-5 text-3xl font-semibold tracking-tight">Application submitted successfully</h1><p className="mt-2 text-muted-foreground">Your application number is</p><div className="mx-auto mt-4 max-w-sm rounded-2xl border bg-muted/30 px-5 py-4 text-2xl font-semibold tracking-wide">{successApplicationNo}</div><p className="mt-4 text-sm text-muted-foreground">Keep this number for future admission communication.</p><Button variant="outline" className="mt-7 rounded-xl" onClick={logout}><LogOut/>Log out</Button></CardContent></Card></div></main></>;

    return <><Head title={`${publicForm.program.name??'Admission'} Application`}/><main className="h-screen overflow-hidden bg-[radial-gradient(circle_at_12%_0%,hsl(var(--primary)/0.13),transparent_26%),radial-gradient(circle_at_92%_12%,hsl(var(--primary)/0.08),transparent_22%)]">
        <div className="mx-auto flex h-full w-full max-w-[1500px] flex-col p-3 md:p-5">
            <header className="relative w-full shrink-0 overflow-hidden rounded-3xl border border-primary/15 bg-background/95 px-5 py-4 shadow-sm md:px-7 md:py-5">
                <div className="pointer-events-none absolute -right-14 -top-24 size-64 rounded-full border-[30px] border-primary/5"/>
                <div className="relative flex items-center justify-between gap-5">
                    <div className="flex min-w-0 items-center gap-4">
                        <div className="grid size-13 shrink-0 place-items-center rounded-2xl border border-primary/20 bg-primary/10"><GraduationCap className="size-6 text-primary"/></div>
                        <div className="min-w-0">
                            <p className="truncate text-sm font-semibold text-primary">{publicForm.college.name} · {publicForm.college.code}</p>
                            <h1 className="mt-0.5 truncate text-2xl font-semibold tracking-tight md:text-3xl">{publicForm.program.name} Admission Application</h1>
                            <div className="mt-1.5 flex flex-wrap gap-x-4 gap-y-0.5 text-sm text-muted-foreground"><span>{publicForm.cycle.name}</span>{publicForm.program.session&&<span>{publicForm.program.session}</span>}{academic.curriculum&&<span>{academic.curriculum.code} · V{academic.curriculum.version}</span>}</div>
                            <p className="mt-0.5 text-xs text-muted-foreground">Application window: {dateLabel(publicForm.cycle.application_start_date)} – {dateLabel(publicForm.cycle.application_end_date)}</p>
                        </div>
                    </div>
                    <Button type="button" variant="outline" className="shrink-0 rounded-xl bg-background/85" onClick={logout}><LogOut/>Log out</Button>
                </div>
                {stepByStep&&<div className="relative mt-4 border-t border-primary/10 pt-3">
                    <div className="min-w-0 overflow-x-auto">
                        <div className="min-w-0 flex-1 overflow-x-auto">
                            <div className="flex min-w-max pr-1">
                                {pageTitles.map((title,index)=>{const active=index===activePage;const unlocked=index<=highestUnlockedPage;const completed=index<highestUnlockedPage;return <button key={`${title}-${index}`} type="button" disabled={!unlocked} onClick={()=>goToPage(index)} className={`relative -ml-px flex min-w-[170px] items-center gap-2 border px-5 py-2.5 text-left text-xs font-semibold transition first:ml-0 ${active?'z-10 border-primary bg-primary text-primary-foreground shadow-sm':completed?'border-primary/25 bg-primary/[0.07] text-foreground hover:bg-primary/[0.11]':unlocked?'border-primary/15 bg-background text-foreground hover:bg-muted/30':'cursor-not-allowed border-border bg-muted/30 text-muted-foreground opacity-55'}`} style={{clipPath:index===0?'polygon(0 0,calc(100% - 14px) 0,100% 50%,calc(100% - 14px) 100%,0 100%)':index===pageTitles.length-1?'polygon(0 0,100% 0,100% 100%,0 100%,14px 50%)':'polygon(0 0,calc(100% - 14px) 0,100% 50%,calc(100% - 14px) 100%,0 100%,14px 50%)'}}><span className={`grid size-6 shrink-0 place-items-center rounded-full text-[11px] ${active?'bg-primary-foreground/20 text-primary-foreground':completed?'bg-primary text-primary-foreground':'bg-muted text-muted-foreground'}`}>{completed?<Check className="size-3.5"/>:index+1}</span><span className="max-w-[118px] truncate">{title}</span></button>})}
                            </div>
                        </div>
                    </div>
                    <p className="mt-2 text-[11px] text-muted-foreground">Complete the current step to unlock the next one. Any completed step stays editable until final submission.</p>
                </div>}
            </header>

            {!publicForm.availability.can_submit?<div className="mt-4 min-h-0 flex-1 overflow-y-auto"><Card className="rounded-3xl"><CardContent className="py-12 text-center"><Clock3 className="mx-auto size-10 text-muted-foreground"/><h2 className="mt-3 text-xl font-semibold">{publicForm.availability.state==='UPCOMING'?'Applications are not open yet':'Applications are currently closed'}</h2><p className="mt-2 text-muted-foreground">{publicForm.availability.message}</p></CardContent></Card></div>:
            <div className="mt-4 grid min-h-0 flex-1 gap-4 lg:grid-cols-[minmax(0,1fr)_320px]">
                <section className="min-h-0 overflow-hidden rounded-3xl border border-primary/10 bg-background/70 shadow-sm backdrop-blur">
                    <div className="h-full overflow-y-auto p-3 md:p-5">
                        <HelpCard help={publicForm.help} className="mb-5"/>
                        <Form action={`/apply/${publicForm.slug}/application`} method="post" noValidate onSubmit={handleFormSubmit} onError={handleSubmitError}>{({processing,errors})=><div className="space-y-5 pb-3">
                            {errors.application&&<div className="rounded-2xl border border-destructive/30 bg-destructive/5 p-4 text-sm text-destructive">{errors.application}</div>}
                            {!errors.application&&Object.keys(errors).length>0&&<div className="rounded-2xl border border-destructive/30 bg-destructive/5 p-4 text-sm text-destructive"><div className="font-medium">We couldn't submit the application yet.</div><div className="mt-1 text-xs opacity-90">Please correct the highlighted field below. Your entered information is still on the form.</div></div>}

            <div data-public-form-page="0" className={stepByStep&&activePage!==0?'hidden':'space-y-5'}>
                <Card className="overflow-hidden rounded-3xl border-primary/15 shadow-sm"><div className="h-1 bg-primary/80"/><CardHeader className="pb-3"><div className="flex items-start gap-3"><div className="grid size-10 place-items-center rounded-xl bg-primary/10"><BookOpenCheck className="size-5 text-primary"/></div><div><CardTitle>Choose your academic path</CardTitle><p className="mt-1 text-sm text-muted-foreground">Select the Discipline and, where applicable, Specialization offered under this Program. Mandatory subjects are auto-allotted from the mapped curriculum; only curriculum-defined choice subjects need your selection.</p></div></div></CardHeader><CardContent className="space-y-5">
                    <div className="grid gap-4 md:grid-cols-2"><div className="space-y-2"><Label>Discipline {disciplines.length>0&&'*'}</Label>{disciplines.length?<select name="academic_preference[discipline_id]" className={selectClass} value={disciplineId} required onChange={e=>selectDiscipline(e.target.value)}><option value="">Select Discipline</option>{disciplines.map(d=><option key={d.id} value={d.id}>{d.name} ({d.code})</option>)}</select>:<div className="rounded-xl border bg-muted/25 px-3 py-3 text-sm text-muted-foreground">No separate Discipline selection is configured for this Program Offering.</div>}{errorFor(errors,'academic_preference.discipline_id')&&<p className="text-xs text-destructive">{errorFor(errors,'academic_preference.discipline_id')}</p>}</div>
                    {specializations.length>0&&<div className="space-y-2"><Label>Specialization {selectedDiscipline?.specialization_required?'*':'(Optional)'}</Label><select name="academic_preference[specialization_id]" className={selectClass} value={specializationId} required={Boolean(selectedDiscipline?.specialization_required)} onChange={e=>selectSpecialization(e.target.value)}><option value="">{selectedDiscipline?.specialization_required?'Select Specialization':'No Specialization / General'}</option>{specializations.map(s=><option key={s.id} value={s.id}>{s.name} ({s.code})</option>)}</select>{errorFor(errors,'academic_preference.specialization_id')&&<p className="text-xs text-destructive">{errorFor(errors,'academic_preference.specialization_id')}</p>}</div>}</div>
                    {(disciplineId||disciplines.length===0)&&academicTerms.length>0&&<div className="space-y-4">
                    <div className="rounded-2xl border border-primary/15 bg-primary/[0.035] p-4">
                        <div className="flex items-start gap-3"><div className="grid size-9 shrink-0 place-items-center rounded-xl bg-primary/10"><BookOpenCheck className="size-5 text-primary"/></div><div><div className="font-medium">Your mandatory curriculum is automatically included</div><p className="mt-1 text-xs leading-5 text-muted-foreground">You do not need to select compulsory subjects semester by semester. {mandatoryCourseCount} applicable mandatory course{mandatoryCourseCount===1?' is':'s are'} derived from the approved curriculum and will follow your student record internally. Only real academic choices are shown below.</p></div></div>
                    </div>
                    {choiceCategories.length===0?<div className="rounded-2xl border bg-muted/15 p-5 text-sm text-muted-foreground">There are no applicant-selectable subjects for this academic context. Continue to the next step; the approved mandatory curriculum will be applied automatically.</div>:<div className="space-y-4">{choiceCategories.map(category=>{
                    if(category.packageMode)return <section key={category.name} className="rounded-2xl border bg-background p-4 md:p-5"><div><h3 className="font-semibold">{category.name}</h3><p className="mt-1 text-xs text-muted-foreground">Choose one academic option. All mapped papers under that option are linked automatically in the background.</p></div><div className="mt-4 grid gap-3 sm:grid-cols-2">{category.packages.map(pkg=>{const checked=packageSelected(category,pkg);return <label key={pkg.key} className={`cursor-pointer rounded-2xl border p-4 transition ${checked?'border-primary/40 bg-primary/5 shadow-sm':'bg-background hover:bg-muted/20'}`}><div className="flex items-center gap-3"><input type="radio" checked={checked} onChange={()=>selectPackage(category,pkg)}/><div><div className="font-semibold">{pkg.name}</div><div className="mt-0.5 text-xs text-muted-foreground">The complete configured academic package will be linked internally.</div></div></div>{checked&&category.slots.flatMap(slot=>(pkg.bySlot[slot.id]??[]).map(mapping=><input key={`${slot.id}-${mapping.id}`} type="hidden" name={`academic_preference[course_choices][${slot.id}][]`} value={mapping.id}/>))}</label>})}</div>{category.slots.map(slot=>errorFor(errors,`academic_preference.course_choices.${slot.id}`)?<p key={slot.id} className="mt-2 text-xs text-destructive">{errorFor(errors,`academic_preference.course_choices.${slot.id}`)}</p>:null)}</section>;
                    const entries=category.slots.flatMap(slot=>slot.mappings.map(mapping=>({slot,mapping})));
                    const groups=entries.reduce<Record<string,{slot:AcademicSlot;mapping:AcademicMapping}[]>>((acc,item)=>{const key=sourceName(item.mapping);(acc[key]??=[]).push(item);return acc;},{});
                    return <section key={category.name} className="rounded-2xl border bg-background p-4 md:p-5"><div><h3 className="font-semibold">{category.name}</h3><p className="mt-1 text-xs text-muted-foreground">Select only where the approved curriculum offers a real academic choice. Term placement and credit structure are handled internally.</p></div><div className="mt-4 space-y-4">{Object.entries(groups).map(([source,items])=><div key={source}><div className="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">{source==='Common / Interdisciplinary'?source:`From ${source}`}</div><div className="grid gap-2 sm:grid-cols-2">{items.map(({slot,mapping})=>{const selected=courseChoices[slot.id]??[];const checked=selected.includes(mapping.id);const max=slot.max_selection??slot.min_selection??1;return <label key={`${slot.id}-${mapping.id}`} className={`flex cursor-pointer items-start gap-2 rounded-xl border px-3 py-3 text-sm transition ${checked?'border-primary/40 bg-primary/5':'bg-background hover:bg-muted/20'}`}><input type="checkbox" name={`academic_preference[course_choices][${slot.id}][]`} value={mapping.id} checked={checked} disabled={!checked&&selected.length>=max} onChange={()=>toggleChoice(slot,mapping.id)}/><span><span className="font-medium">{mapping.course_code}</span><span className="block text-xs text-muted-foreground">{mapping.course_name}</span></span></label>})}</div></div>)}</div>{category.slots.map(slot=>errorFor(errors,`academic_preference.course_choices.${slot.id}`)?<p key={slot.id} className="mt-2 text-xs text-destructive">{errorFor(errors,`academic_preference.course_choices.${slot.id}`)}</p>:null)}</section>;
                })}</div>}
                </div>}
                </CardContent></Card>
            </div>



            {steps.map((step,index)=><div key={step.id} data-public-form-page={index+1} className={stepByStep&&activePage!==index+1?'hidden':''}><Card className="overflow-hidden rounded-3xl shadow-sm"><CardHeader className="border-b bg-muted/[0.08]"><div className="flex items-start justify-between gap-4"><div><p className="text-xs font-semibold uppercase tracking-[0.18em] text-primary">Application Step {index+1}</p><CardTitle className="mt-1">{step.title}</CardTitle>{step.description&&<p className="mt-1 text-sm text-muted-foreground">{step.description}</p>}</div><Sparkles className="size-5 text-primary/50"/></div></CardHeader><CardContent className="space-y-5 pt-5">{safe(step.panels).map(panel=>{const panelFields=safe(step.fields).filter(f=>f.college_admission_form_panel_id===panel.id);if(!panelFields.length)return null;return <section key={panel.id} className="rounded-2xl border bg-muted/[0.08] p-4 md:p-5"><div className="mb-4"><h3 className="font-semibold">{panel.title}</h3>{panel.description&&<p className="mt-1 text-sm text-muted-foreground">{panel.description}</p>}</div><div className="grid gap-5 md:grid-cols-2">{panelFields.map(field=><DynamicField key={field.id} field={field} values={values} setValue={setValue} errors={errors}/>)}</div></section>})}<div className="grid gap-5 md:grid-cols-2">{safe(step.fields).filter(f=>!f.college_admission_form_panel_id).map(field=><DynamicField key={field.id} field={field} values={values} setValue={setValue} errors={errors}/>)}</div></CardContent></Card></div>)}

            <div data-public-form-page={totalPages-1} className={stepByStep&&activePage!==totalPages-1?'hidden':''}><Card className="overflow-hidden rounded-3xl border-primary/15 shadow-lg"><div className="h-1.5 bg-primary"/><CardHeader><div className="flex items-start gap-3"><div className="grid size-11 place-items-center rounded-xl bg-primary/10"><FileCheck2 className="size-6 text-primary"/></div><div><p className="text-xs font-semibold uppercase tracking-[0.18em] text-primary">Final Preview</p><CardTitle className="mt-1 text-2xl">Review your application</CardTitle><p className="mt-1 text-sm text-muted-foreground">Check your academic selection, profile and entered details before final submission.</p></div></div></CardHeader><CardContent className="space-y-5">
                <div className="grid gap-4 md:grid-cols-2"><section className="rounded-2xl border p-4"><h3 className="font-semibold">Academic Selection</h3><dl className="mt-3 space-y-2 text-sm"><div className="flex justify-between gap-4"><dt className="text-muted-foreground">Program</dt><dd className="text-right font-medium">{publicForm.program.name}</dd></div><div className="flex justify-between gap-4"><dt className="text-muted-foreground">Discipline</dt><dd className="text-right font-medium">{selectedDiscipline?.name??'Program-wide'}</dd></div>{specializations.length>0&&<div className="flex justify-between gap-4"><dt className="text-muted-foreground">Specialization</dt><dd className="text-right font-medium">{specializations.find(s=>String(s.id)===specializationId)?.name??'General / No Specialization'}</dd></div>}</dl></section><section className="rounded-2xl border p-4"><div className="flex items-start gap-4">{profilePhotoPreview&&<img src={profilePhotoPreview} alt="Candidate profile preview" className="size-20 shrink-0 rounded-xl border object-cover"/>}<div className="min-w-0 flex-1"><h3 className="font-semibold">Applicant</h3><dl className="mt-3 space-y-2 text-sm"><div className="flex justify-between gap-4"><dt className="text-muted-foreground">Name</dt><dd className="text-right font-medium">{applicant.name}</dd></div><div className="flex justify-between gap-4"><dt className="text-muted-foreground">Email</dt><dd className="text-right font-medium">{applicant.email}</dd></div><div className="flex justify-between gap-4"><dt className="text-muted-foreground">Phone</dt><dd className="text-right font-medium">{applicant.phone??'—'}</dd></div></dl></div></div></section></div>
                {academicTerms.length>0&&<section className="rounded-2xl border p-4"><h3 className="font-semibold">Academic Courses</h3><p className="mt-1 text-xs text-muted-foreground">{mandatoryCourseCount} mandatory course{mandatoryCourseCount===1?'':'s'} will be inherited automatically from the approved curriculum.</p><div className="mt-3 grid gap-3 md:grid-cols-2">{choiceTerms.flatMap(term=>term.slots.map(slot=>{const mappings=slot.mappings.filter(m=>(courseChoices[slot.id]??[]).includes(m.id));if(!mappings.length)return null;return <div key={`${term.id}-${slot.id}`} className="rounded-xl bg-muted/[0.18] p-3"><div className="text-xs font-medium text-muted-foreground">{term.name} · {slot.category_name??'Choice Courses'}</div><div className="mt-1 text-sm">{mappings.map(m=>`${m.course_code} — ${m.course_name}${m.source_discipline_name?` (from ${m.source_discipline_name})`:''}`).join(', ')}</div></div>}))}</div></section>}
                {dynamicSummary.length>0&&<section className="rounded-2xl border p-4"><h3 className="font-semibold">Application Details</h3><div className="mt-3 grid gap-3 md:grid-cols-2">{dynamicSummary.map((item,index)=><div key={`${item.label}-${index}`} className="rounded-xl bg-muted/[0.18] p-3"><div className="text-xs text-muted-foreground">{item.step} · {item.label}</div><div className="mt-1 text-sm font-medium">{item.value}</div></div>)}</div></section>}
                <section className="rounded-2xl border border-primary/15 bg-primary/[0.035] p-4"><div className="flex flex-wrap items-center justify-between gap-4"><div><div className="font-medium">Application Fee</div><p className="mt-1 text-sm text-muted-foreground">{publicForm.fee.required?`${publicForm.fee.currency} ${Number(publicForm.fee.amount).toFixed(2)} is configured for this application.`:'No application fee is configured for this application.'}</p></div><div className="flex items-center gap-2 text-sm text-muted-foreground"><ShieldCheck className="size-4 text-primary"/>Seat allocation is processed later</div></div></section>
                <label className="flex cursor-pointer items-start gap-3 rounded-2xl border p-4"><input type="checkbox" name="preview_confirmed" value="1" required className="mt-1"/><span><span className="font-medium">I have reviewed the application</span><span className="mt-1 block text-xs leading-5 text-muted-foreground">I confirm that the information and academic choices shown above are correct to the best of my knowledge.</span></span></label>{errors.preview_confirmed&&<p className="text-xs text-destructive">{errors.preview_confirmed}</p>}
                {!stepByStep&&<div className="flex justify-end"><Button type="submit" size="lg" className="rounded-xl px-7" disabled={processing}>{processing&&<Spinner/>}Submit Application</Button></div>}
            </CardContent></Card></div>

                            {stepByStep&&<div className="sticky bottom-1 z-20"><Card className="rounded-2xl border-primary/15 bg-background/95 shadow-lg backdrop-blur"><CardContent className="flex items-center justify-between gap-3 p-3"><Button type="button" variant="outline" className="rounded-xl" onClick={previousPage} disabled={activePage===0}><ArrowLeft/>Previous</Button><div className="hidden text-center sm:block"><div className="text-xs text-muted-foreground">Step {activePage+1} of {totalPages}</div><div className="text-sm font-medium">{pageTitles[activePage]}</div></div>{activePage<totalPages-1?<Button type="button" className="rounded-xl" onClick={nextPage}>Continue<ArrowRight/></Button>:<Button type="submit" className="rounded-xl" disabled={processing}>{processing&&<Spinner/>}Submit Application</Button>}</CardContent></Card></div>}
                        </div>}</Form>
                    </div>
                </section>

                <aside className="hidden min-h-0 lg:block">
                    <div className="h-full min-h-0">
                        <Card className="shrink-0 overflow-hidden rounded-3xl border-primary/15 shadow-sm">
                            <div className="h-1.5 bg-primary"/>
                            <CardContent className="p-5">
                                <div className="flex items-center gap-3">
                                    <div className="grid size-11 place-items-center rounded-2xl bg-primary/10"><UserRoundCheck className="size-5 text-primary"/></div>
                                    <div className="min-w-0"><div className="text-xs font-semibold uppercase tracking-[0.16em] text-primary">Applicant Profile</div><div className="truncate text-lg font-semibold">{applicant.name}</div></div>
                                </div>
                                <div className="mt-4 rounded-2xl border border-primary/15 bg-primary/[0.04] p-3">
                                    <div className="text-[11px] font-medium uppercase tracking-[0.14em] text-muted-foreground">Registration Number</div>
                                    <div className="mt-1 font-mono text-sm font-semibold tracking-wide">{applicant.registration_no??applicant.registration_no_preview??'Assigned on final submission'}</div>{!applicant.registration_no&&<div className="mt-1 text-[11px] text-muted-foreground">Current format preview · final sequence is reserved on submission</div>}
                                </div>
                                <dl className="mt-4 space-y-3 text-sm">
                                    <div><dt className="text-xs text-muted-foreground">Date of Birth</dt><dd className="mt-0.5 font-medium">{applicant.date_of_birth??'—'}</dd></div>
                                    <div><dt className="text-xs text-muted-foreground">Email</dt><dd className="mt-0.5 break-all font-medium">{applicant.email}</dd></div>
                                    <div><dt className="text-xs text-muted-foreground">Phone</dt><dd className="mt-0.5 font-medium">{applicant.phone??'—'}</dd></div>
                                </dl>
                            </CardContent>
                        </Card>


                        {stepByStep&&<Card className="mt-4 overflow-hidden rounded-3xl border-primary/15 shadow-sm">
                            <CardContent className="p-5">
                                <div className="flex items-center gap-4">
                                    <div className="relative grid size-20 shrink-0 place-items-center rounded-full" style={{background:`conic-gradient(hsl(var(--primary)) ${Math.round(((highestUnlockedPage+1)/totalPages)*360)}deg,hsl(var(--muted)) 0deg)`}}>
                                        <div className="grid size-14 place-items-center rounded-full bg-background text-center shadow-sm"><div><div className="text-base font-bold">{Math.round(((highestUnlockedPage+1)/totalPages)*100)}%</div><div className="text-[9px] uppercase tracking-wide text-muted-foreground">Complete</div></div></div>
                                    </div>
                                    <div className="min-w-0"><div className="text-xs font-semibold uppercase tracking-[0.15em] text-primary">Application Progress</div><div className="mt-1 text-lg font-semibold">Step {activePage+1} of {totalPages}</div><div className="mt-1 truncate text-xs text-muted-foreground">{pageTitles[activePage]}</div></div>
                                </div>
                                <div className="mt-4 h-1.5 overflow-hidden rounded-full bg-muted"><div className="h-full rounded-full bg-primary transition-all" style={{width:`${Math.round(((highestUnlockedPage+1)/totalPages)*100)}%`}}/></div>
                            </CardContent>
                        </Card>}

                    </div>
                </aside>
            </div>}
        </div>
    </main></>;
}

(PublicAdmissionApplication as typeof PublicAdmissionApplication & {layout?: (page:ReactNode)=>ReactNode}).layout=(page)=>page;
export default PublicAdmissionApplication;
