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
import { Loader2, Plus, Pencil, Trash2, Trophy, UserPlus, UserMinus } from "lucide-react";

export default function AdminBadgesPage() {
  const [badges, setBadges] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [showForm, setShowForm] = useState(false);
  const [editing, setEditing] = useState<any>(null);
  const [form, setForm] = useState({ nom: "", description: "", icone: "", condition: "" });
  const [actionLoading, setActionLoading] = useState(false);
  const [showAttribuer, setShowAttribuer] = useState<any>(null);
  const [userId, setUserId] = useState("");

  const fetchAll = useCallback(async () => {
    setLoading(true);
    try {
      const res = await apiClient.get<any>("/v1/admin/badges");
      setBadges(res.data?.badges || res.data || []);
    } catch (e: any) { setError(e.message); }
    setLoading(false);
  }, []);

  useEffect(() => { fetchAll(); }, [fetchAll]);

  const handleCreate = async () => {
    setActionLoading(true);
    try { await apiClient.post("/v1/admin/badges", form); setShowForm(false); setForm({ nom: "", description: "", icone: "", condition: "" }); fetchAll(); } catch (e: any) { setError(e.message); }
    setActionLoading(false);
  };

  const handleUpdate = async () => {
    if (!editing) return;
    setActionLoading(true);
    try { await apiClient.put(`/v1/admin/badges/${editing.id}`, form); setEditing(null); setShowForm(false); fetchAll(); } catch (e: any) { setError(e.message); }
    setActionLoading(false);
  };

  const handleDelete = async (id: number) => {
    if (!confirm("Supprimer ce badge ?")) return;
    try { await apiClient.delete(`/v1/admin/badges/${id}`); fetchAll(); } catch (e: any) { setError(e.message); }
  };

  const handleAttribuer = async () => {
    if (!showAttribuer || !userId) return;
    setActionLoading(true);
    try { await apiClient.post(`/v1/admin/badges/${showAttribuer.id}/attribuer/${userId}`); setShowAttribuer(null); setUserId(""); } catch (e: any) { setError(e.message); }
    setActionLoading(false);
  };

  const handleRetirer = async (badgeId: number) => {
    if (!userId) return;
    try { await apiClient.delete(`/v1/admin/badges/${badgeId}/retirer/${userId}`); } catch (e: any) { setError(e.message); }
  };

  if (loading) return <div className="py-12 text-center"><Loader2 className="h-8 w-8 animate-spin mx-auto" /></div>;

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div><h1 className="text-2xl font-bold flex items-center gap-2"><Trophy className="h-6 w-6" /> Gestion des badges</h1><p className="text-muted-foreground">{badges.length} badges</p></div>
        <Button onClick={() => { setEditing(null); setForm({ nom: "", description: "", icone: "", condition: "" }); setShowForm(true); }}><Plus className="h-4 w-4 mr-2" /> Nouveau badge</Button>
      </div>
      {error && <div className="text-red-600 text-sm bg-red-50 p-3 rounded-lg">{error}</div>}

      {badges.length === 0 ? <Card><CardContent className="py-12 text-center text-muted-foreground">Aucun badge.</CardContent></Card> : (
        <Table>
          <TableHeader><TableRow><TableHead>Nom</TableHead><TableHead>Icône</TableHead><TableHead>Description</TableHead><TableHead>Condition</TableHead><TableHead>Actions</TableHead></TableRow></TableHeader>
          <TableBody>
            {badges.map((b: any) => (
              <TableRow key={b.id}>
                <TableCell className="font-semibold">{b.nom}</TableCell>
                <TableCell>{b.icone || "🏆"}</TableCell>
                <TableCell className="max-w-xs truncate">{b.description || "-"}</TableCell>
                <TableCell className="text-xs">{b.condition || "-"}</TableCell>
                <TableCell className="flex gap-1">
                  <Button size="sm" variant="ghost" onClick={() => { setShowAttribuer(b); setUserId(""); }}><UserPlus className="h-3 w-3" /></Button>
                  <Button size="sm" variant="ghost" onClick={() => { setEditing(b); setForm({ nom: b.nom, description: b.description || "", icone: b.icone || "", condition: b.condition || "" }); setShowForm(true); }}><Pencil className="h-3 w-3" /></Button>
                  <Button size="sm" variant="ghost" className="text-red-500" onClick={() => handleDelete(b.id)}><Trash2 className="h-3 w-3" /></Button>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      )}

      <Dialog open={showForm} onOpenChange={v => { setShowForm(v); if (!v) setEditing(null); }}>
        <DialogContent><DialogHeader><DialogTitle>{editing ? "Modifier" : "Nouveau"} badge</DialogTitle><DialogDescription>Remplissez les informations</DialogDescription></DialogHeader>
          <div className="space-y-4">
            <div><Label>Nom</Label><Input value={form.nom} onChange={e => setForm({ ...form, nom: e.target.value })} /></div>
            <div><Label>Icône (emoji)</Label><Input value={form.icone} onChange={e => setForm({ ...form, icone: e.target.value })} placeholder="🏆" /></div>
            <div><Label>Description</Label><Textarea value={form.description} onChange={e => setForm({ ...form, description: e.target.value })} rows={2} /></div>
            <div><Label>Condition d'obtention</Label><Input value={form.condition} onChange={e => setForm({ ...form, condition: e.target.value })} placeholder="Ex: 10 projets complétés" /></div>
          </div>
          <DialogFooter><Button variant="outline" onClick={() => setShowForm(false)}>Annuler</Button><Button onClick={editing ? handleUpdate : handleCreate} disabled={actionLoading || !form.nom}>{actionLoading ? <Loader2 className="h-4 w-4 animate-spin" /> : editing ? "Enregistrer" : "Créer"}</Button></DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Attribuer Dialog */}
      <Dialog open={!!showAttribuer} onOpenChange={() => setShowAttribuer(null)}>
        <DialogContent><DialogHeader><DialogTitle>Attribuer le badge</DialogTitle><DialogDescription>{showAttribuer?.nom}</DialogDescription></DialogHeader>
          <div><Label>ID Utilisateur</Label><Input value={userId} onChange={e => setUserId(e.target.value)} placeholder="ID de l'utilisateur" /></div>
          <DialogFooter><Button variant="outline" onClick={() => setShowAttribuer(null)}>Annuler</Button><Button onClick={handleAttribuer} disabled={actionLoading || !userId}>{actionLoading ? <Loader2 className="h-4 w-4 animate-spin" /> : "Attribuer"}</Button></DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
