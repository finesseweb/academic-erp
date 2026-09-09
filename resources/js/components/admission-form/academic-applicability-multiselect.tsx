import { Search, X } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Item = { id:number; name:string; code?:string; degree_level_id?:number; degree_id?:number; program_template_id?:number };

type Props = {
  degreeLevels: Item[];
  degrees: Item[];
  programs: Item[];
  curricula: Item[];
  offerings?: Item[];
  cycles?: Item[];
  initial?: {
    degree_level_ids?: number[];
    degree_ids?: number[];
    program_template_ids?: number[];
    curriculum_ids?: number[];
    college_program_offering_ids?: number[];
    college_admission_cycle_ids?: number[];
  };
};

type MultiProps={name:string;label:string;items:Item[];selected:number[];setSelected:(ids:number[])=>void;empty:string};

function MultiPicker({name,label,items,selected,setSelected,empty}:MultiProps){
  const [search,setSearch]=useState('');
  const filtered=useMemo(()=>{
    const q=search.trim().toLowerCase();
    if(!q)return items;
    return items.filter(item=>`${item.name} ${item.code??''}`.toLowerCase().includes(q));
  },[items,search]);
  const toggle=(id:number)=>setSelected(selected.includes(id)?selected.filter(x=>x!==id):[...selected,id]);
  return <div className="space-y-2">
    <Label>{label}</Label>
    <details className="group rounded-md border bg-background">
      <summary className="flex min-h-10 cursor-pointer list-none items-center justify-between gap-2 px-3 py-2 text-sm">
        <span className={selected.length?'font-medium':'text-muted-foreground'}>{selected.length?`${selected.length} selected`:empty}</span>
        <span className="text-xs text-muted-foreground">Select</span>
      </summary>
      <div className="border-t p-2">
        <div className="relative mb-2"><Search className="absolute left-2.5 top-2.5 size-4 text-muted-foreground"/><Input value={search} onChange={e=>setSearch(e.target.value)} className="h-9 pl-8" placeholder={`Search ${label.toLowerCase()}...`}/></div>
        {selected.length>0&&<button type="button" className="mb-2 inline-flex items-center gap-1 text-xs text-primary" onClick={()=>setSelected([])}><X className="size-3"/>Clear selection</button>}
        <div className="max-h-44 space-y-1 overflow-y-auto pr-1">
          {filtered.length===0?<div className="px-2 py-3 text-xs text-muted-foreground">No matching option.</div>:filtered.map(item=><label key={item.id} className="flex cursor-pointer items-start gap-2 rounded px-2 py-2 text-sm hover:bg-muted/40"><input type="checkbox" name={`${name}[]`} value={item.id} checked={selected.includes(item.id)} onChange={()=>toggle(item.id)} className="mt-0.5 size-4"/><span><span className="font-medium">{item.name}</span>{item.code&&<span className="ml-1 text-xs text-muted-foreground">· {item.code}</span>}</span></label>)}
        </div>
      </div>
    </details>
  </div>;
}

export function AcademicApplicabilityMultiSelect({degreeLevels,degrees,programs,curricula,offerings=[],cycles=[],initial={}}:Props){
  const [degreeLevelIds,setDegreeLevelIds]=useState<number[]>(initial.degree_level_ids??[]);
  const [degreeIds,setDegreeIds]=useState<number[]>(initial.degree_ids??[]);
  const [programIds,setProgramIds]=useState<number[]>(initial.program_template_ids??[]);
  const [curriculumIds,setCurriculumIds]=useState<number[]>(initial.curriculum_ids??[]);
  const [offeringIds,setOfferingIds]=useState<number[]>(initial.college_program_offering_ids??[]);
  const [cycleIds,setCycleIds]=useState<number[]>(initial.college_admission_cycle_ids??[]);

  const filteredDegrees=degreeLevelIds.length?degrees.filter(d=>d.degree_level_id&&degreeLevelIds.includes(Number(d.degree_level_id))):degrees;
  const filteredPrograms=degreeIds.length?programs.filter(p=>p.degree_id&&degreeIds.includes(Number(p.degree_id))):programs;
  const filteredCurricula=programIds.length?curricula.filter(c=>c.program_template_id&&programIds.includes(Number(c.program_template_id))):curricula;

  return <div className="sm:col-span-2 rounded-lg border p-4">
    <div className="font-medium">Academic applicability <span className="text-muted-foreground">(optional)</span></div>
    <p className="mb-3 text-xs text-muted-foreground">Leave everything blank for every application. Multiple values inside one level use OR; configured levels are combined with AND against the Admission Cycle&apos;s linked Program Offering and current Curriculum.</p>
    <div className="grid gap-3 sm:grid-cols-2">
      <MultiPicker name="degree_level_ids" label="Degree Level" items={degreeLevels} selected={degreeLevelIds} setSelected={ids=>{setDegreeLevelIds(ids);setDegreeIds(v=>v.filter(id=>degrees.some(d=>d.id===id&&(!ids.length||ids.includes(Number(d.degree_level_id))))));}} empty="Any degree level"/>
      <MultiPicker name="degree_ids" label="Degree" items={filteredDegrees} selected={degreeIds} setSelected={ids=>{setDegreeIds(ids);setProgramIds(v=>v.filter(id=>programs.some(p=>p.id===id&&(!ids.length||ids.includes(Number(p.degree_id))))));}} empty="Any degree"/>
      <MultiPicker name="program_template_ids" label="Program" items={filteredPrograms} selected={programIds} setSelected={ids=>{setProgramIds(ids);setCurriculumIds(v=>v.filter(id=>curricula.some(c=>c.id===id&&(!ids.length||ids.includes(Number(c.program_template_id))))));}} empty="Any program"/>
      <MultiPicker name="curriculum_ids" label="Curriculum" items={filteredCurricula} selected={curriculumIds} setSelected={setCurriculumIds} empty="Any current curriculum"/>
      {offerings.length>0&&<MultiPicker name="college_program_offering_ids" label="Program Offering" items={offerings} selected={offeringIds} setSelected={setOfferingIds} empty="Any offering"/>}
      {cycles.length>0&&<MultiPicker name="college_admission_cycle_ids" label="Admission Cycle" items={cycles} selected={cycleIds} setSelected={setCycleIds} empty="Any cycle"/>}
    </div>
    <p className="mt-3 text-xs text-muted-foreground">Runtime authority: Admission Cycle → Program Offering → Program/Degree/Degree Level + linked current Curriculum. The same resolver is enforced during rendering and submission validation.</p>
  </div>;
}
