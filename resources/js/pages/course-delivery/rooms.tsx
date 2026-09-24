import { Form, Head } from '@inertiajs/react';
import { Building2, Pencil, Plus } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
type Room = {
    id: number;
    code: string;
    name: string;
    building: string | null;
    floor: string | null;
    room_type: string;
    capacity: number | null;
    status: string;
    notes: string | null;
};
type Props = {
    college: { id: number; name: string; code: string; status: string };
    rooms: Room[];
    can: { manage: boolean };
};
function RoomForm({ collegeId, row }: { collegeId: number; row?: Room }) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button
                    size={row ? 'icon' : 'sm'}
                    variant={row ? 'ghost' : 'default'}
                >
                    {row ? (
                        <Pencil />
                    ) : (
                        <>
                            <Plus />
                            Add Room
                        </>
                    )}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>{row ? 'Edit Room' : 'Add Room'}</DialogTitle>
                <DialogDescription>
                    College-owned physical teaching space for Timetable and
                    Class Scheduling.
                </DialogDescription>
                <Form
                    method={row ? 'patch' : 'post'}
                    action={
                        row
                            ? `/college/${collegeId}/rooms/${row.id}`
                            : `/college/${collegeId}/rooms`
                    }
                    onSuccess={() => setOpen(false)}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <Label>Code</Label>
                                    <Input
                                        name="code"
                                        defaultValue={row?.code}
                                    />
                                    <p className="text-xs text-destructive">
                                        {errors.code}
                                    </p>
                                </div>
                                <div>
                                    <Label>Name</Label>
                                    <Input
                                        name="name"
                                        defaultValue={row?.name}
                                    />
                                </div>
                                <div>
                                    <Label>Building</Label>
                                    <Input
                                        name="building"
                                        defaultValue={row?.building ?? ''}
                                    />
                                </div>
                                <div>
                                    <Label>Floor</Label>
                                    <Input
                                        name="floor"
                                        defaultValue={row?.floor ?? ''}
                                    />
                                </div>
                                <div>
                                    <Label>Capacity</Label>
                                    <Input
                                        name="capacity"
                                        type="number"
                                        min="1"
                                        defaultValue={row?.capacity ?? ''}
                                    />
                                </div>
                                <div>
                                    <Label>Type</Label>
                                    <Select
                                        name="room_type"
                                        defaultValue={
                                            row?.room_type ?? 'CLASSROOM'
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {[
                                                'CLASSROOM',
                                                'LAB',
                                                'SEMINAR',
                                                'OTHER',
                                            ].map((x) => (
                                                <SelectItem key={x} value={x}>
                                                    {x}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div>
                                    <Label>Status</Label>
                                    <Select
                                        name="status"
                                        defaultValue={row?.status ?? 'ACTIVE'}
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="ACTIVE">
                                                Active
                                            </SelectItem>
                                            <SelectItem value="INACTIVE">
                                                Inactive
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                            <div>
                                <Label>Notes</Label>
                                <Textarea
                                    name="notes"
                                    defaultValue={row?.notes ?? ''}
                                />
                            </div>
                            <DialogFooter>
                                <Button disabled={processing}>
                                    {processing && <Spinner />}Save Room
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
export default function Rooms({ college, rooms, can }: Props) {
    return (
        <>
            <Head title={`${college.name} Rooms`} />
            <div className="space-y-6 p-4 md:p-6">
                <header className="flex justify-between border-b pb-5">
                    <div>
                        <p className="text-sm font-medium text-primary">
                            {college.code} · Course Delivery
                        </p>
                        <h1 className="text-3xl font-semibold">Rooms</h1>
                        <p className="text-muted-foreground">
                            Manage classrooms, labs and teaching spaces used by
                            the timetable.
                        </p>
                    </div>
                    {can.manage && <RoomForm collegeId={college.id} />}
                </header>
                <Card>
                    <CardHeader>
                        <CardTitle className="flex gap-2">
                            <Building2 />
                            Teaching Spaces
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr>
                                        {[
                                            'Code / Room',
                                            'Location',
                                            'Type',
                                            'Capacity',
                                            'Status',
                                            'Action',
                                        ].map((x) => (
                                            <th
                                                className="p-3 text-left"
                                                key={x}
                                            >
                                                {x}
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {rooms.map((r) => (
                                        <tr className="border-t" key={r.id}>
                                            <td className="p-3">
                                                <b>{r.code}</b>
                                                <div>{r.name}</div>
                                            </td>
                                            <td className="p-3">
                                                {[r.building, r.floor]
                                                    .filter(Boolean)
                                                    .join(' · ') || '—'}
                                            </td>
                                            <td className="p-3">
                                                {r.room_type}
                                            </td>
                                            <td className="p-3">
                                                {r.capacity ?? '—'}
                                            </td>
                                            <td className="p-3">
                                                <Badge
                                                    variant={
                                                        r.status === 'ACTIVE'
                                                            ? 'default'
                                                            : 'secondary'
                                                    }
                                                >
                                                    {r.status}
                                                </Badge>
                                            </td>
                                            <td className="p-3">
                                                {can.manage && (
                                                    <RoomForm
                                                        collegeId={college.id}
                                                        row={r}
                                                    />
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                    {!rooms.length && (
                                        <tr>
                                            <td
                                                colSpan={6}
                                                className="p-10 text-center text-muted-foreground"
                                            >
                                                No Rooms configured.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
