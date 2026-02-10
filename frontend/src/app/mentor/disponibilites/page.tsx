"use client";
import { useEffect, useState, useCallback } from "react";
import { apiClient } from "@/lib/api";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from "@/components/ui/dialog";
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from "@/components/ui/table";
import { Loader2, Plus, Pencil, Trash2, Calendar, Clock } from "lucide-react";

type Disponibilite = {
  id: number; jour: string; heure_debut: string; heure_fin: string;
  type?: string; recurrent?: boolean; created_at?: string;
};

const JOURS = ["lundi", "mardi", "mercredi", "jeudi", "vendredi", "samedi", "dimanche"];

export default function MentorDisponibilitesPage() {
  const [disponibilites, setDisponibilites] = useState<Disponibilite[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [showForm, setShowForm] = useState(false);
  const [editing, setEditing] = useState<Disponibilite | null>(null);
  const [actionLoading, setActionLoading] = useState(false);
  const [form, setForm] = useState({ jour: "lundi", heure_debut: "09:00", heure_fin: "10:00", type: "en_ligne", recurrent: true });

  const fetchAll = useCallback(async () => {
    setLoading(true);
    try {
      const res = await apiClient.get<any>("/v1/mentor/disponibilites");
      setDisponibilites(res.data?.disponibilites || res.data || []);
    } catch (e: any) { setError(e.message); }
    setLoading(false);
  }, []);

  useEffect(() => { fetchAll(); }, [fetchAll]);

  const handleCreate = async () => {
    setActionLoading(true);
    try {
      await apiClient.post("/v1/mentor/disponibilites", form);
      setShowForm(false);
      resetForm();
      fetchAll();
    } catch (e: any) { setError(e.message); }
    setActionLoading(false);
  };

  const handleUpdate = async () => {
    if (!editing) return;
    setActionLoading(true);
    try {
      await apiClient.put(`/v1/mentor/disponibilites/${editing.id}`, form);
      setShowForm(false);
      setEditing(null);
      resetForm();
      fetchAll();
    } catch (e: any) { setError(e.message); }
    setActionLoading(false);
  };

  const handleDelete = async (id: number) => {
    if (!confirm("Supprimer ce créneau ?")) return;
    try { await apiClient.delete(`/v1/mentor/disponibilites/${id}`); fetchAll(); } catch (e: any) { setError(e.message); }
  };

  const resetForm = () => setForm({ jour: "lundi", heure_debut: "09:00", heure_fin: "10:00", type: "en_ligne", recurrent: true });

  const openEdit = (d: Disponibilite) => {
    setEditing(d);
    setForm({ jour: d.jour, heure_debut: d.heure_debut, heure_fin: d.heure_fin, type: d.type || "en_ligne", recurrent: d.recurrent ?? true });
    setShowForm(true);
  };

  // Group by day for calendar-like view
  const grouped = JOURS.reduce((acc, jour) => {
    acc[jour] = disponibilites.filter(d => d.jour === jour);
    return acc;
  }, {} as Record<string, Disponibilite[]>);

  if (loading) return <div className="py-12 text-center"><Loader2 className="h-8 w-8 animate-spin mx-auto" /></div>;

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div><h1 className="text-2xl font-bold flex items-center gap-2"><Calendar className="h-6 w-6" /> Disponibilités</h1><p className="text-muted-foreground">Gérez vos créneaux de disponibilité</p></div>
        <Button onClick={() => { resetForm(); setEditing(null); setShowForm(true); }}><Plus className="h-4 w-4 mr-2" /> Ajouter un créneau</Button>
      </div>
      {error && <div className="text-red-600 text-sm bg-red-50 p-3 rounded-lg">{error}</div>}

      {/* Calendar-like view */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        {JOURS.map(jour => (
          <Card key={jour}>
            <CardContent className="p-4">
              <h3 className="font-semibold capitalize mb-3">{jour}</h3>
              {grouped[jour].length === 0 ? <p className="text-xs text-muted-foreground">Aucun créneau</p> : (
                <div className="space-y-2">
                  {grouped[jour].map(d => (
                    <div key={d.id} className="flex items-center justify-between bg-muted rounded-lg px-3 py-2">
                      <div className="flex items-center gap-2">
                        <Clock className="h-3 w-3 text-muted-foreground" />
                        <span className="text-sm font-medium">{d.heure_debut} - {d.heure_fin}</span>
                        {d.type && <Badge variant="outline" className="text-xs">{d.type}</Badge>}
                      </div>
                      <div className="flex gap-1">
                        <Button size="sm" variant="ghost" onClick={() => openEdit(d)}><Pencil className="h-3 w-3" /></Button>
                        <Button size="sm" variant="ghost" className="text-red-500" onClick={() => handleDelete(d.id)}><Trash2 className="h-3 w-3" /></Button>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>
        ))}
      </div>

      {/* Table view */}
      {disponibilites.length > 0 && (
        <>
          <h3 className="text-lg font-semibold mt-6">Vue détaillée</h3>
          <Table>
            <TableHeader><TableRow><TableHead>Jour</TableHead><TableHead>Début</TableHead><TableHead>Fin</TableHead><TableHead>Type</TableHead><TableHead>Récurrent</TableHead><TableHead>Actions</TableHead></TableRow></TableHeader>
            <TableBody>
              {disponibilites.map(d => (
                <TableRow key={d.id}>
                  <TableCell className="capitalize">{d.jour}</TableCell>
                  <TableCell>{d.heure_debut}</TableCell>
                  <TableCell>{d.heure_fin}</TableCell>
                  <TableCell><Badge variant="outline">{d.type || "-"}</Badge></TableCell>
                  <TableCell>{d.recurrent ? "Oui" : "Non"}</TableCell>
                  <TableCell className="flex gap-1"><Button size="sm" variant="ghost" onClick={() => openEdit(d)}><Pencil className="h-3 w-3" /></Button><Button size="sm" variant="ghost" className="text-red-500" onClick={() => handleDelete(d.id)}><Trash2 className="h-3 w-3" /></Button></TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </>
      )}

      {/* Create/Edit Dialog */}
      <Dialog open={showForm} onOpenChange={v => { setShowForm(v); if (!v) { setEditing(null); resetForm(); } }}>
        <DialogContent><DialogHeader><DialogTitle>{editing ? "Modifier le créneau" : "Nouveau créneau"}</DialogTitle><DialogDescription>Définissez votre disponibilité</DialogDescription></DialogHeader>
          <div className="space-y-4">
            <div><Label>Jour</Label><Select value={form.jour} onValueChange={v => setForm({ ...form, jour: v })}><SelectTrigger><SelectValue /></SelectTrigger><SelectContent>{JOURS.map(j => <SelectItem key={j} value={j} className="capitalize">{j}</SelectItem>)}</SelectContent></Select></div>
            <div className="grid grid-cols-2 gap-4"><div><Label>Heure début</Label><Input type="time" value={form.heure_debut} onChange={e => setForm({ ...form, heure_debut: e.target.value })} /></div><div><Label>Heure fin</Label><Input type="time" value={form.heure_fin} onChange={e => setForm({ ...form, heure_fin: e.target.value })} /></div></div>
            <div><Label>Type</Label><Select value={form.type} onValueChange={v => setForm({ ...form, type: v })}><SelectTrigger><SelectValue /></SelectTrigger><SelectContent><SelectItem value="en_ligne">En ligne</SelectItem><SelectItem value="presentiel">Présentiel</SelectItem><SelectItem value="hybride">Hybride</SelectItem></SelectContent></Select></div>
          </div>
          <DialogFooter><Button variant="outline" onClick={() => setShowForm(false)}>Annuler</Button><Button onClick={editing ? handleUpdate : handleCreate} disabled={actionLoading}>{actionLoading ? <Loader2 className="h-4 w-4 animate-spin" /> : editing ? "Enregistrer" : "Créer"}</Button></DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
