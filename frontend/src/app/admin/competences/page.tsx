"use client";
import { useEffect, useState, useCallback } from "react";
import { apiClient } from "@/lib/api";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from "@/components/ui/table";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from "@/components/ui/dialog";
import { Loader2, Plus, Pencil, Trash2, BrainCircuit } from "lucide-react";

export default function AdminCompetencesPage() {
  const [competences, setCompetences] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [showForm, setShowForm] = useState(false);
  const [editing, setEditing] = useState<any>(null);
  const [form, setForm] = useState({ nom: "", description: "", categorie: "" });
  const [actionLoading, setActionLoading] = useState(false);

  const fetchAll = useCallback(async () => {
    setLoading(true);
    try {
      const res = await apiClient.get<any>("/v1/admin/competences");
      setCompetences(res.data?.competences || res.data || []);
    } catch (e: any) { setError(e.message); }
    setLoading(false);
  }, []);

  useEffect(() => { fetchAll(); }, [fetchAll]);

  const handleCreate = async () => {
    setActionLoading(true);
    try { await apiClient.post("/v1/admin/competences", form); setShowForm(false); setForm({ nom: "", description: "", categorie: "" }); fetchAll(); } catch (e: any) { setError(e.message); }
    setActionLoading(false);
  };

  const handleUpdate = async () => {
    if (!editing) return;
    setActionLoading(true);
    try { await apiClient.put(`/v1/admin/competences/${editing.id}`, form); setEditing(null); setShowForm(false); fetchAll(); } catch (e: any) { setError(e.message); }
    setActionLoading(false);
  };

  const handleDelete = async (id: number) => {
    if (!confirm("Supprimer cette compétence ?")) return;
    try { await apiClient.delete(`/v1/admin/competences/${id}`); fetchAll(); } catch (e: any) { setError(e.message); }
  };

  if (loading) return <div className="py-12 text-center"><Loader2 className="h-8 w-8 animate-spin mx-auto" /></div>;

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div><h1 className="text-2xl font-bold flex items-center gap-2"><BrainCircuit className="h-6 w-6" /> Gestion des compétences</h1><p className="text-muted-foreground">{competences.length} compétences</p></div>
        <Button onClick={() => { setEditing(null); setForm({ nom: "", description: "", categorie: "" }); setShowForm(true); }}><Plus className="h-4 w-4 mr-2" /> Nouvelle compétence</Button>
      </div>
      {error && <div className="text-red-600 text-sm bg-red-50 p-3 rounded-lg">{error}</div>}

      {competences.length === 0 ? <Card><CardContent className="py-12 text-center text-muted-foreground">Aucune compétence.</CardContent></Card> : (
        <Table>
          <TableHeader><TableRow><TableHead>Nom</TableHead><TableHead>Catégorie</TableHead><TableHead>Description</TableHead><TableHead>Actions</TableHead></TableRow></TableHeader>
          <TableBody>
            {competences.map((c: any) => (
              <TableRow key={c.id}>
                <TableCell className="font-semibold">{c.nom}</TableCell>
                <TableCell><Badge variant="outline">{c.categorie || "-"}</Badge></TableCell>
                <TableCell className="max-w-xs truncate">{c.description || "-"}</TableCell>
                <TableCell className="flex gap-1">
                  <Button size="sm" variant="ghost" onClick={() => { setEditing(c); setForm({ nom: c.nom, description: c.description || "", categorie: c.categorie || "" }); setShowForm(true); }}><Pencil className="h-3 w-3" /></Button>
                  <Button size="sm" variant="ghost" className="text-red-500" onClick={() => handleDelete(c.id)}><Trash2 className="h-3 w-3" /></Button>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      )}

      <Dialog open={showForm} onOpenChange={v => { setShowForm(v); if (!v) setEditing(null); }}>
        <DialogContent><DialogHeader><DialogTitle>{editing ? "Modifier" : "Nouvelle"} compétence</DialogTitle><DialogDescription>Remplissez les informations</DialogDescription></DialogHeader>
          <div className="space-y-4">
            <div><Label>Nom</Label><Input value={form.nom} onChange={e => setForm({ ...form, nom: e.target.value })} /></div>
            <div><Label>Catégorie</Label><Input value={form.categorie} onChange={e => setForm({ ...form, categorie: e.target.value })} placeholder="Frontend, Backend..." /></div>
            <div><Label>Description</Label><Textarea value={form.description} onChange={e => setForm({ ...form, description: e.target.value })} rows={3} /></div>
          </div>
          <DialogFooter><Button variant="outline" onClick={() => setShowForm(false)}>Annuler</Button><Button onClick={editing ? handleUpdate : handleCreate} disabled={actionLoading || !form.nom}>{actionLoading ? <Loader2 className="h-4 w-4 animate-spin" /> : editing ? "Enregistrer" : "Créer"}</Button></DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
