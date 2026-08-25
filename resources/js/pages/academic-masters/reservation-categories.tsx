import { Form, Head, router } from '@inertiajs/react';
import { Pencil, Plus, Power } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';

type Category = {
    id:number; name:string; code:string; nature:'VERTICAL'|'HORIZONTAL';
    description:string|null; display_order:number; status:'ACTIVE'|'INACTIVE';
};
type Props={categories:Category[];can:{create:boolean;update:boolean;enable:boolean;disable:boolean}};

function CategoryForm({category}:{category?:Category}) {
    const action=category?`/admin/reservation-categories/${category.id}`:'/admin/reservation-categories';
    return <Dialog>
        <DialogTrigger asChild>
            <Button variant={category?'ghost':'default'} size={category?'icon':'default'}>
                {category?<Pencil/>:<Plus/>}{!category&&'Add Category'}
            </Button>
        </DialogTrigger>
        <DialogContent className="w-[calc(100vw-2rem)] max-w-lg sm:w-full">
            <DialogTitle>{category?'Edit':'Add'} Reservation / Quota Category</DialogTitle>
            <DialogDescription>Vertical categories partition seats; Horizontal categories overlay the same seat capacity.</DialogDescription>
            <Form action={action} method={category?'patch':'post'} className="space-y-4">
                {({processing,errors})=><>
                    <div className="space-y-2"><Label>Name</Label><Input name="name" defaultValue={category?.name}/>{errors.name&&<p className="text-xs text-destructive">{errors.name}</p>}</div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2"><Label>Code</Label><Input name="code" defaultValue={category?.code}/></div>
                        <div className="space-y-2"><Label>Display Order</Label><Input type="number" min={0} name="display_order" defaultValue={category?.display_order??0}/></div>
                    </div>
                    <div className="space-y-2"><Label>Nature</Label>
                        <Select name="nature" defaultValue={category?.nature??'VERTICAL'}>
                            <SelectTrigger className="w-full"><SelectValue/></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="VERTICAL">Vertical — partitions physical seats</SelectItem>
                                <SelectItem value="HORIZONTAL">Horizontal — overlays physical seats</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="space-y-2"><Label>Description</Label><textarea name="description" defaultValue={category?.description??''} className="min-h-24 w-full resize-y rounded-md border bg-background px-3 py-2 text-sm"/></div>
                    <DialogFooter><DialogClose asChild><Button type="button" variant="outline">Cancel</Button></DialogClose><Button type="submit" disabled={processing}>{processing&&<Spinner/>}Save</Button></DialogFooter>
                </>}
            </Form>
        </DialogContent>
    </Dialog>;
}

export default function ReservationCategories({categories,can}:Props){
    const toggle=(c:Category)=>router.patch(`/admin/reservation-categories/${c.id}/status`,{status:c.status==='ACTIVE'?'INACTIVE':'ACTIVE'},{preserveScroll:true});
    return <>
        <Head title="Reservation / Quota Categories"/>
        <div className="space-y-6 p-4 md:p-6">
            <header className="flex flex-wrap items-end justify-between gap-3 border-b pb-5">
                <div><p className="text-sm font-medium text-primary">University Academic Setup</p><h1 className="text-3xl font-semibold">Reservation / Quota Categories</h1><p className="text-sm text-muted-foreground">University-owned configurable categories. Names are not hard-coded.</p></div>
                {can.create&&<CategoryForm/>}
            </header>
            <Card><CardHeader><CardTitle>Category Master</CardTitle></CardHeader><CardContent className="p-0">
                {categories.length===0?<div className="p-8 text-sm text-muted-foreground">No categories configured.</div>:
                <div className="overflow-x-auto"><table className="w-full text-sm"><thead className="bg-muted/50 text-left"><tr><th className="p-3">Order</th><th className="p-3">Category</th><th className="p-3">Nature</th><th className="p-3">Status</th><th className="p-3 text-right">Actions</th></tr></thead><tbody>
                {categories.map(c=><tr key={c.id} className="border-t"><td className="p-3">{c.display_order}</td><td className="p-3"><div className="font-medium">{c.name}</div><div className="text-xs text-muted-foreground">{c.code}</div></td><td className="p-3">{c.nature}</td><td className="p-3">{c.status}</td><td className="p-3"><div className="flex justify-end gap-1">{can.update&&<CategoryForm category={c}/>} {(c.status==='ACTIVE'?can.disable:can.enable)&&<Button type="button" variant="ghost" size="icon" onClick={()=>toggle(c)}><Power className="size-4"/></Button>}</div></td></tr>)}
                </tbody></table></div>}
            </CardContent></Card>
        </div>
    </>;
}
