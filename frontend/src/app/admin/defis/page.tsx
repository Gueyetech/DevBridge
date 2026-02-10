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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Loader2, Plus, Pencil, Trash2, Swords, Play, Pause, Eye } from "lucide-react";

export default function AdminDefisPage() {
  const [defis, setDefis] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [showForm, setShowForm] = useState(false);
  const [editing, setEditing] = useState<any>(null);
  const [form, setForm] = useState({ titre: "", description: "", difficulte: "intermediaire", points: "100", date_limite: "" });
  const [actionLoading, setActionLoading] = useState(false);
  const [participations, setParticipations] = useState<any[]>([]);
  const [showPart, setShowPart] = useState<any>(null);

  const fetchAll = useCallback(async () => {
    setLoading(true);
    try {
      const res = await apiClient.get<any>("/v1/admin/defis");
      setDefis(res.data?.defis || res.data || []);
    } catch (e: any) { setError(e.message); }
    setLoading(false);
  }, []);

  useEffect(() => { fetchAll(); }, [fetchAll]);

  const handleCreate = async () => {
    setActionLoading(true);
    try { await apiClient.post("/v1/admin/defis", { ...form, points: parseInt(form.points) }); setShowForm(false); setForm({ titre: "", description: "", difficulte: "intermediaire", points: "100", date_limite: "" }); fetchAll(); } catch (e: any) { setError(e.message); }
    setActionLoading(false);
  };

  const handleUpdate = async () => {
    if (!editing) return;
    setActionLoading(true);
    try { await apiClient.put(`/v1/admin/defis/${editing.id}`, { ...form, points: parseInt(form.points) }); setEditing(null); setShowForm(false); fetchAll(); } catch (e: any) { setError(e.message); }
    setActionLoading(false);
  };

  const handleDelete = async (id: number) => {
    if (!confirm("Supprimer ce défi ?")) return;
    try { await apiClient.delete(`/v1/admin/defis/${id}`); fetchAll(); } catch (e: any) { setError(e.message); }
  };

  const handleActivate = async (id: number) => {
    try { await apiClient.post(`/v1/admin/defis/${id}/activer`); fetchAll(); } catch (e: any) { setError(e.message); }
  };

  const handleDeactivate = async (id: number) => {
    try { await apiClient.post(`/v1/admin/defis/${id}/desactiver`); fetchAll(); } catch (e: any) { setError(e.message); }
  };

  const fetchParticipations = async (defi: any) => {
    try {
      const res = await apiClient.get<any>(`/v1/admin/defis/${defi.id}/participations`);
      setParticipations(res.data?.participations || res.data || []);
      setShowPart(defi);
    } catch (e: any) { setError(e.message); }
  };

  if (loading) return <div className="py-12 text-center"><Loader2 className="h-8 w-8 animate-spin mx-auto" /></div>;

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div><h1 className="text-2xl font-bold flex items-center gap-2"><Swords className="h-6 w-6" /> Gestion des défis</h1><p className="text-muted-foreground">{defis.length} défis</p></div>
        <Button onClick={() => { setEditing(null); setForm({ titre: "", description: "", difficulte: "intermediaire", points: "100", date_limite: "" }); setShowForm(true); }}><Plus className="h-4 w-4 mr-2" /> Nouveau défi</Button>
      </div>
      {error && <div className="text-red-600 text-sm bg-red-50 p-3 rounded-lg">{error}</div>}

      {defis.length === 0 ? <Card><CardContent className="py-12 text-center text-muted-foreground">Aucun défi.</CardContent></Card> : (
        <Table>
          <TableHeader><TableRow><TableHead>Titre</TableHead><TableHead>Difficulté</TableHead><TableHead>Points</TableHead><TableHead>Statut</TableHead><TableHead>Date limite</TableHead><TableHead>Actions</TableHead></TableRow></TableHeader>
          <TableBody>
            {defis.map((d: any) => (
              <TableRow key={d.id}>
                <TableCell className="font-semibold">{d.titre}</TableCell>
                <TableCell><Badge variant="outline">{d.difficulte}</Badge></TableCell>
                <TableCell>{d.points}</TableCell>
                <TableCell><Badge variant={d.actif || d.statut === "actif" ? "default" : "secondary"}>{d.actif || d.statut === "actif" ? "Actif" : "Inactif"}</Badge></TableCell>
                <TableCell className="text-xs">{d.date_limite ? new Date(d.date_limite).toLocaleDateString("fr-FR") : "-"}</TableCell>
                <TableCell className="flex gap-1">
                  <Button size="sm" variant="ghost" onClick={() => fetchParticipations(d)}><Eye className="h-3 w-3" /></Button>
                  <Button size="sm" variant="ghost" onClick={() => { setEditing(d); setForm({ titre: d.titre, description: d.description || "", difficulte: d.difficulte || "intermediaire", points: String(d.points || 100), date_limite: d.date_limite || "" }); setShowForm(true); }}><Pencil className="h-3 w-3" /></Button>
                  {d.actif || d.statut === "actif" ? <Button size="sm" variant="ghost" onClick={() => handleDeactivate(d.id)}><Pause className="h-3 w-3" /></Button> : <Button size="sm" variant="ghost" onClick={() => handleActivate(d.id)}><Play className="h-3 w-3" /></Button>}
                  <Button size="sm" variant="ghost" className="text-red-500" onClick={() => handleDelete(d.id)}><Trash2 className="h-3 w-3" /></Button>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      )}

      <Dialog open={showForm} onOpenChange={v => { setShowForm(v); if (!v) setEditing(null); }}>
        <DialogContent><DialogHeader><DialogTitle>{editing ? "Modifier" : "Nouveau"} défi</DialogTitle><DialogDescription>Remplissez les informations du défi</DialogDescription></DialogHeader>
          <div className="space-y-4">
            <div><Label>Titre</Label><Input value={form.titre} onChange={e => setForm({ ...form, titre: e.target.value })} /></div>
            <div><Label>Description</Label><Textarea value={form.description} onChange={e => setForm({ ...form, description: e.target.value })} rows={3} /></div>
            <div className="grid grid-cols-2 gap-4">
              <div><Label>Difficulté</Label><Select value={form.difficulte} onValueChange={v => setForm({ ...form, difficulte: v })}><SelectTrigger><SelectValue /></SelectTrigger><SelectContent><SelectItem value="debutant">Débutant</SelectItem><SelectItem value="intermediaire">Intermédiaire</SelectItem><SelectItem value="avance">Avancé</SelectItem><SelectItem value="expert">Expert</SelectItem></SelectContent></Select></div>
              <div><Label>Points</Label><Input type="number" value={form.points} onChange={e => setForm({ ...form, points: e.target.value })} /></div>
            </div>
            <div><Label>Date limite</Label><Input type="date" value={form.date_limite} onChange={e => setForm({ ...form, date_limite: e.target.value })} /></div>
          </div>
          <DialogFooter><Button variant="outline" onClick={() => setShowForm(false)}>Annuler</Button><Button onClick={editing ? handleUpdate : handleCreate} disabled={actionLoading || !form.titre}>{actionLoading ? <Loader2 className="h-4 w-4 animate-spin" /> : editing ? "Enregistrer" : "Créer"}</Button></DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Participations Dialog */}
      {showPart && (
        <Dialog open onOpenChange={() => setShowPart(null)}>
          <DialogContent className="max-w-lg"><DialogHeader><DialogTitle>Participations - {showPart.titre}</DialogTitle></DialogHeader>
            {participations.length === 0 ? <p className="text-muted-foreground text-center py-8">Aucune participation.</p> : (
              <Table><TableHeader><TableRow><TableHead>Étudiant</TableHead><TableHead>Statut</TableHead><TableHead>Score</TableHead></TableRow></TableHeader>
                <TableBody>{participations.map((p: any, i: number) => (
                  <TableRow key={i}><TableCell>{p.etudiant?.prenom || p.utilisateur?.prenom} {p.etudiant?.nom || p.utilisateur?.nom}</TableCell><TableCell><Badge variant="outline">{p.statut}</Badge></TableCell><TableCell>{p.score || "-"}</TableCell></TableRow>
                ))}</TableBody></Table>
            )}
            <DialogFooter><Button variant="outline" onClick={() => setShowPart(null)}>Fermer</Button></DialogFooter>
          </DialogContent>
        </Dialog>
      )}
    </div>
  );
}
