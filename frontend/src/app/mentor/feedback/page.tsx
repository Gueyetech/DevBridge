"use client";
import { useEffect, useState, useCallback } from "react";
import { apiClient } from "@/lib/api";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Separator } from "@/components/ui/separator";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from "@/components/ui/dialog";
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from "@/components/ui/table";
import { Loader2, Plus, Pencil, Trash2, MessageSquare, Eye, Code, FolderKanban } from "lucide-react";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";

type Feedback = {
  id: number; type?: string; contenu?: string; note?: number;
  etudiant?: { id: number; prenom: string; nom: string };
  projet?: { id: number; titre: string };
  created_at?: string;
};

export default function MentorFeedbackPage() {
  const [feedbacks, setFeedbacks] = useState<Feedback[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [showForm, setShowForm] = useState(false);
  const [feedbackType, setFeedbackType] = useState<"code" | "projet">("code");
  const [actionLoading, setActionLoading] = useState(false);
  const [selected, setSelected] = useState<Feedback | null>(null);
  const [editing, setEditing] = useState<Feedback | null>(null);

  const [codeForm, setCodeForm] = useState({ etudiant_id: "", contenu: "", code_source: "", langage: "", note: "3", suggestions: "" });
  const [projetForm, setProjetForm] = useState({ projet_id: "", contenu: "", note: "3", points_positifs: "", points_amelioration: "" });

  const fetchAll = useCallback(async () => {
    setLoading(true);
    try {
      const res = await apiClient.get<any>("/v1/mentor/feedback");
      setFeedbacks(res.data?.feedbacks || res.data || []);
    } catch (e: any) { setError(e.message); }
    setLoading(false);
  }, []);

  useEffect(() => { fetchAll(); }, [fetchAll]);

  const handleCreateCode = async () => {
    setActionLoading(true);
    try {
      await apiClient.post("/v1/mentor/feedback/code", {
        ...codeForm, note: parseInt(codeForm.note),
      });
      setShowForm(false);
      fetchAll();
    } catch (e: any) { setError(e.message); }
    setActionLoading(false);
  };

  const handleCreateProjet = async () => {
    setActionLoading(true);
    try {
      await apiClient.post("/v1/mentor/feedback/projet", {
        ...projetForm, note: parseInt(projetForm.note),
      });
      setShowForm(false);
      fetchAll();
    } catch (e: any) { setError(e.message); }
    setActionLoading(false);
  };

  const handleUpdate = async () => {
    if (!editing) return;
    setActionLoading(true);
    try {
      await apiClient.put(`/v1/mentor/feedback/${editing.id}`, {
        contenu: editing.contenu, note: editing.note,
      });
      setEditing(null);
      fetchAll();
    } catch (e: any) { setError(e.message); }
    setActionLoading(false);
  };

  const handleDelete = async (id: number) => {
    if (!confirm("Supprimer ce feedback ?")) return;
    try { await apiClient.delete(`/v1/mentor/feedback/${id}`); fetchAll(); } catch (e: any) { setError(e.message); }
  };

  if (loading) return <div className="py-12 text-center"><Loader2 className="h-8 w-8 animate-spin mx-auto" /></div>;

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div><h1 className="text-2xl font-bold flex items-center gap-2"><MessageSquare className="h-6 w-6" /> Feedback</h1><p className="text-muted-foreground">Donnez et gérez vos feedback code et projets</p></div>
        <Button onClick={() => setShowForm(true)}><Plus className="h-4 w-4 mr-2" /> Nouveau feedback</Button>
      </div>
      {error && <div className="text-red-600 text-sm bg-red-50 p-3 rounded-lg">{error}</div>}

      <div className="grid grid-cols-2 gap-4">
        <Card className="p-4 text-center"><p className="text-2xl font-bold">{feedbacks.length}</p><p className="text-xs text-muted-foreground">Total feedback</p></Card>
        <Card className="p-4 text-center"><p className="text-2xl font-bold">{feedbacks.length > 0 ? (feedbacks.reduce((s, f) => s + (f.note || 0), 0) / feedbacks.length).toFixed(1) : "0"}</p><p className="text-xs text-muted-foreground">Note moyenne</p></Card>
      </div>

      {feedbacks.length === 0 ? <Card><CardContent className="py-12 text-center text-muted-foreground">Aucun feedback donné.</CardContent></Card> : (
        <Table>
          <TableHeader><TableRow><TableHead>Type</TableHead><TableHead>Étudiant / Projet</TableHead><TableHead>Note</TableHead><TableHead>Contenu</TableHead><TableHead>Date</TableHead><TableHead>Actions</TableHead></TableRow></TableHeader>
          <TableBody>
            {feedbacks.map(f => (
              <TableRow key={f.id}>
                <TableCell><Badge variant="outline">{f.type || "general"}</Badge></TableCell>
                <TableCell>{f.etudiant ? `${f.etudiant.prenom} ${f.etudiant.nom}` : f.projet?.titre || "-"}</TableCell>
                <TableCell>{f.note ? `${f.note}/5` : "-"}</TableCell>
                <TableCell className="max-w-xs truncate">{f.contenu}</TableCell>
                <TableCell className="text-xs text-muted-foreground">{f.created_at ? new Date(f.created_at).toLocaleDateString("fr-FR") : "-"}</TableCell>
                <TableCell className="flex gap-1">
                  <Button size="sm" variant="ghost" onClick={() => setSelected(f)}><Eye className="h-3 w-3" /></Button>
                  <Button size="sm" variant="ghost" onClick={() => setEditing(f)}><Pencil className="h-3 w-3" /></Button>
                  <Button size="sm" variant="ghost" className="text-red-500" onClick={() => handleDelete(f.id)}><Trash2 className="h-3 w-3" /></Button>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      )}

      {/* Create Dialog */}
      <Dialog open={showForm} onOpenChange={setShowForm}>
        <DialogContent className="max-w-lg">
          <DialogHeader><DialogTitle>Nouveau feedback</DialogTitle><DialogDescription>Choisissez le type de feedback</DialogDescription></DialogHeader>
          <Tabs value={feedbackType} onValueChange={v => setFeedbackType(v as "code" | "projet")}>
            <TabsList className="w-full"><TabsTrigger value="code" className="flex-1"><Code className="h-3 w-3 mr-1" /> Code</TabsTrigger><TabsTrigger value="projet" className="flex-1"><FolderKanban className="h-3 w-3 mr-1" /> Projet</TabsTrigger></TabsList>
            <TabsContent value="code" className="space-y-3 mt-3">
              <div><Label>ID Étudiant</Label><Input value={codeForm.etudiant_id} onChange={e => setCodeForm({ ...codeForm, etudiant_id: e.target.value })} placeholder="ID de l'étudiant" /></div>
              <div><Label>Langage</Label><Input value={codeForm.langage} onChange={e => setCodeForm({ ...codeForm, langage: e.target.value })} placeholder="JavaScript, Python..." /></div>
              <div><Label>Code source</Label><Textarea value={codeForm.code_source} onChange={e => setCodeForm({ ...codeForm, code_source: e.target.value })} className="font-mono text-sm" rows={4} /></div>
              <div><Label>Commentaire</Label><Textarea value={codeForm.contenu} onChange={e => setCodeForm({ ...codeForm, contenu: e.target.value })} rows={3} /></div>
              <div><Label>Note</Label><Select value={codeForm.note} onValueChange={v => setCodeForm({ ...codeForm, note: v })}><SelectTrigger><SelectValue /></SelectTrigger><SelectContent>{[1,2,3,4,5].map(n => <SelectItem key={n} value={String(n)}>{n}/5</SelectItem>)}</SelectContent></Select></div>
              <Button onClick={handleCreateCode} disabled={actionLoading} className="w-full">{actionLoading ? <Loader2 className="h-4 w-4 animate-spin" /> : "Envoyer feedback code"}</Button>
            </TabsContent>
            <TabsContent value="projet" className="space-y-3 mt-3">
              <div><Label>ID Projet</Label><Input value={projetForm.projet_id} onChange={e => setProjetForm({ ...projetForm, projet_id: e.target.value })} placeholder="ID du projet" /></div>
              <div><Label>Commentaire</Label><Textarea value={projetForm.contenu} onChange={e => setProjetForm({ ...projetForm, contenu: e.target.value })} rows={3} /></div>
              <div><Label>Points positifs</Label><Textarea value={projetForm.points_positifs} onChange={e => setProjetForm({ ...projetForm, points_positifs: e.target.value })} rows={2} /></div>
              <div><Label>Points à améliorer</Label><Textarea value={projetForm.points_amelioration} onChange={e => setProjetForm({ ...projetForm, points_amelioration: e.target.value })} rows={2} /></div>
              <div><Label>Note</Label><Select value={projetForm.note} onValueChange={v => setProjetForm({ ...projetForm, note: v })}><SelectTrigger><SelectValue /></SelectTrigger><SelectContent>{[1,2,3,4,5].map(n => <SelectItem key={n} value={String(n)}>{n}/5</SelectItem>)}</SelectContent></Select></div>
              <Button onClick={handleCreateProjet} disabled={actionLoading} className="w-full">{actionLoading ? <Loader2 className="h-4 w-4 animate-spin" /> : "Envoyer feedback projet"}</Button>
            </TabsContent>
          </Tabs>
        </DialogContent>
      </Dialog>

      {/* Detail Dialog */}
      {selected && (
        <Dialog open onOpenChange={() => setSelected(null)}>
          <DialogContent><DialogHeader><DialogTitle>Détail du feedback</DialogTitle></DialogHeader>
            <div className="space-y-2 text-sm">
              <div><span className="font-semibold">Type :</span> {selected.type}</div>
              {selected.etudiant && <div><span className="font-semibold">Étudiant :</span> {selected.etudiant.prenom} {selected.etudiant.nom}</div>}
              {selected.projet && <div><span className="font-semibold">Projet :</span> {selected.projet.titre}</div>}
              <div><span className="font-semibold">Note :</span> {selected.note}/5</div>
              <div><span className="font-semibold">Contenu :</span><p className="mt-1 whitespace-pre-wrap">{selected.contenu}</p></div>
            </div>
            <DialogFooter><Button variant="outline" onClick={() => setSelected(null)}>Fermer</Button></DialogFooter>
          </DialogContent>
        </Dialog>
      )}

      {/* Edit Dialog */}
      {editing && (
        <Dialog open onOpenChange={() => setEditing(null)}>
          <DialogContent><DialogHeader><DialogTitle>Modifier le feedback</DialogTitle></DialogHeader>
            <div className="space-y-3">
              <div><Label>Contenu</Label><Textarea value={editing.contenu || ""} onChange={e => setEditing({ ...editing, contenu: e.target.value })} rows={4} /></div>
              <div><Label>Note</Label><Select value={String(editing.note || 3)} onValueChange={v => setEditing({ ...editing, note: parseInt(v) })}><SelectTrigger><SelectValue /></SelectTrigger><SelectContent>{[1,2,3,4,5].map(n => <SelectItem key={n} value={String(n)}>{n}/5</SelectItem>)}</SelectContent></Select></div>
            </div>
            <DialogFooter><Button variant="outline" onClick={() => setEditing(null)}>Annuler</Button><Button onClick={handleUpdate} disabled={actionLoading}>{actionLoading ? <Loader2 className="h-4 w-4 animate-spin" /> : "Enregistrer"}</Button></DialogFooter>
          </DialogContent>
        </Dialog>
      )}
    </div>
  );
}
