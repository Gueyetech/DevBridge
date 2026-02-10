"use client";
import { useEffect, useState, useCallback } from "react";
import { apiClient } from "@/lib/api";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Separator } from "@/components/ui/separator";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription } from "@/components/ui/dialog";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import {
  Plus, Loader2, Pencil, Trash2, Users, CheckCircle, LogIn,
  ListTodo, MessageSquare, Send
} from "lucide-react";

type Tache = {
  id: number; titre: string; description?: string; statut: string;
  priorite?: string; assignee?: { id: number; prenom: string; nom: string };
  created_at?: string;
};

type Commentaire = {
  id: number; contenu: string; utilisateur?: { id: number; prenom: string; nom: string };
  created_at?: string;
};

type Projet = {
  id: number; titre: string; description?: string; statut: string;
  technologie?: string; difficulte?: string; nombre_membres?: number;
  membres?: { id: number; prenom: string; nom: string; role_projet?: string }[];
  created_at?: string;
};

export default function EtudiantProjetsPage() {
  const [projets, setProjets] = useState<Projet[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [showCreate, setShowCreate] = useState(false);
  const [showEdit, setShowEdit] = useState(false);
  const [selectedProjet, setSelectedProjet] = useState<Projet | null>(null);
  const [showDetail, setShowDetail] = useState(false);
  const [actionLoading, setActionLoading] = useState(false);
  const [taches, setTaches] = useState<Tache[]>([]);
  const [commentaires, setCommentaires] = useState<Record<number, Commentaire[]>>({});
  const [showTaskForm, setShowTaskForm] = useState(false);
  const [showComments, setShowComments] = useState<number | null>(null);
  const [newComment, setNewComment] = useState("");
  const [form, setForm] = useState({ titre: "", description: "", technologie: "", difficulte: "debutant" });
  const [taskForm, setTaskForm] = useState({ titre: "", description: "", priorite: "normale" });

  const fetchProjets = useCallback(async () => {
    setLoading(true);
    try {
      const res = await apiClient.get<any>("/v1/etudiant/projets");
      setProjets(res.data?.projets || res.data || []);
    } catch (e: any) { setError(e.message); } finally { setLoading(false); }
  }, []);

  useEffect(() => { fetchProjets(); }, [fetchProjets]);

  const fetchTaches = async (projetId: number) => {
    try {
      const res = await apiClient.get<any>(`/v1/etudiant/projets/${projetId}/taches`);
      setTaches(res.data?.taches || res.data || []);
    } catch { setTaches([]); }
  };

  const fetchCommentaires = async (projetId: number, tacheId: number) => {
    try {
      const res = await apiClient.get<any>(`/v1/etudiant/projets/${projetId}/taches/${tacheId}/commentaires`);
      setCommentaires(prev => ({ ...prev, [tacheId]: res.data?.commentaires || res.data || [] }));
    } catch { /* ignore */ }
  };

  const handleCreate = async () => {
    setActionLoading(true);
    try {
      await apiClient.post("/v1/etudiant/projets", form);
      setShowCreate(false);
      setForm({ titre: "", description: "", technologie: "", difficulte: "debutant" });
      fetchProjets();
    } catch (e: any) { setError(e.message); }
    setActionLoading(false);
  };

  const handleEdit = async () => {
    if (!selectedProjet) return;
    setActionLoading(true);
    try {
      await apiClient.put(`/v1/etudiant/projets/${selectedProjet.id}`, form);
      setShowEdit(false);
      fetchProjets();
    } catch (e: any) { setError(e.message); }
    setActionLoading(false);
  };

  const handleDelete = async (id: number) => {
    if (!confirm("Supprimer ce projet ?")) return;
    try { await apiClient.delete(`/v1/etudiant/projets/${id}`); fetchProjets(); } catch (e: any) { setError(e.message); }
  };

  const handleJoin = async (id: number) => {
    try { await apiClient.post(`/v1/etudiant/projets/${id}/rejoindre`); fetchProjets(); } catch (e: any) { setError(e.message); }
  };

  const handleComplete = async (id: number) => {
    try { await apiClient.post(`/v1/etudiant/projets/${id}/completer`); fetchProjets(); } catch (e: any) { setError(e.message); }
  };

  const openDetail = async (projet: Projet) => {
    setSelectedProjet(projet);
    setShowDetail(true);
    await fetchTaches(projet.id);
  };

  const handleCreateTask = async () => {
    if (!selectedProjet) return;
    setActionLoading(true);
    try {
      await apiClient.post(`/v1/etudiant/projets/${selectedProjet.id}/taches`, taskForm);
      setShowTaskForm(false);
      setTaskForm({ titre: "", description: "", priorite: "normale" });
      fetchTaches(selectedProjet.id);
    } catch (e: any) { setError(e.message); }
    setActionLoading(false);
  };

  const handleCompleteTask = async (tacheId: number) => {
    if (!selectedProjet) return;
    try { await apiClient.post(`/v1/etudiant/projets/${selectedProjet.id}/taches/${tacheId}/terminer`); fetchTaches(selectedProjet.id); } catch (e: any) { setError(e.message); }
  };

  const handleDeleteTask = async (tacheId: number) => {
    if (!selectedProjet) return;
    try { await apiClient.delete(`/v1/etudiant/projets/${selectedProjet.id}/taches/${tacheId}`); fetchTaches(selectedProjet.id); } catch (e: any) { setError(e.message); }
  };

  const handleAddComment = async (tacheId: number) => {
    if (!selectedProjet || !newComment.trim()) return;
    try {
      await apiClient.post(`/v1/etudiant/projets/${selectedProjet.id}/taches/${tacheId}/commentaires`, { contenu: newComment });
      setNewComment("");
      fetchCommentaires(selectedProjet.id, tacheId);
    } catch (e: any) { setError(e.message); }
  };

  const statutBadge = (s: string) => {
    if (s === "en_cours") return "default";
    if (s === "termine") return "secondary";
    return "outline";
  };

  if (loading) return <div className="py-12 text-center"><Loader2 className="h-8 w-8 animate-spin mx-auto" /></div>;

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div><h1 className="text-2xl font-bold">Mes projets</h1><p className="text-muted-foreground">Gérez vos projets, tâches et collaborations</p></div>
        <Button onClick={() => { setForm({ titre: "", description: "", technologie: "", difficulte: "debutant" }); setShowCreate(true); }}><Plus className="h-4 w-4 mr-2" /> Nouveau projet</Button>
      </div>
      {error && <div className="text-red-600 text-sm bg-red-50 p-3 rounded-lg">{error} <Button size="sm" variant="ghost" onClick={() => setError(null)}>x</Button></div>}
      {projets.length === 0 ? (
        <Card><CardContent className="py-12 text-center text-muted-foreground">Aucun projet. Créez votre premier projet !</CardContent></Card>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {projets.map(p => (
            <Card key={p.id} className="cursor-pointer hover:shadow-md transition-shadow" onClick={() => openDetail(p)}>
              <CardHeader className="pb-2">
                <div className="flex justify-between items-start"><CardTitle className="text-lg">{p.titre}</CardTitle><Badge variant={statutBadge(p.statut)}>{p.statut}</Badge></div>
                <CardDescription className="line-clamp-2">{p.description}</CardDescription>
              </CardHeader>
              <CardContent>
                <div className="flex flex-wrap gap-2 text-xs text-muted-foreground">
                  {p.technologie && <Badge variant="outline">{p.technologie}</Badge>}
                  {p.difficulte && <Badge variant="outline">{p.difficulte}</Badge>}
                  <span className="flex items-center gap-1"><Users className="h-3 w-3" /> {p.nombre_membres || p.membres?.length || 0}</span>
                </div>
                <div className="flex gap-1 mt-3" onClick={e => e.stopPropagation()}>
                  <Button size="sm" variant="ghost" onClick={() => { setSelectedProjet(p); setForm({ titre: p.titre, description: p.description || "", technologie: p.technologie || "", difficulte: p.difficulte || "debutant" }); setShowEdit(true); }}><Pencil className="h-3 w-3" /></Button>
                  <Button size="sm" variant="ghost" onClick={() => handleJoin(p.id)}><LogIn className="h-3 w-3" /></Button>
                  {p.statut !== "termine" && <Button size="sm" variant="ghost" onClick={() => handleComplete(p.id)}><CheckCircle className="h-3 w-3" /></Button>}
                  <Button size="sm" variant="ghost" className="text-red-500" onClick={() => handleDelete(p.id)}><Trash2 className="h-3 w-3" /></Button>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      )}

      {/* Create Dialog */}
      <Dialog open={showCreate} onOpenChange={setShowCreate}>
        <DialogContent><DialogHeader><DialogTitle>Nouveau projet</DialogTitle><DialogDescription>Remplissez les informations</DialogDescription></DialogHeader>
          <div className="space-y-4">
            <div><Label>Titre</Label><Input value={form.titre} onChange={e => setForm({ ...form, titre: e.target.value })} /></div>
            <div><Label>Description</Label><Textarea value={form.description} onChange={e => setForm({ ...form, description: e.target.value })} /></div>
            <div><Label>Technologie</Label><Input value={form.technologie} onChange={e => setForm({ ...form, technologie: e.target.value })} placeholder="React, Python..." /></div>
            <div><Label>Difficulté</Label><Select value={form.difficulte} onValueChange={v => setForm({ ...form, difficulte: v })}><SelectTrigger><SelectValue /></SelectTrigger><SelectContent><SelectItem value="debutant">Débutant</SelectItem><SelectItem value="intermediaire">Intermédiaire</SelectItem><SelectItem value="avance">Avancé</SelectItem></SelectContent></Select></div>
          </div>
          <DialogFooter><Button variant="outline" onClick={() => setShowCreate(false)}>Annuler</Button><Button onClick={handleCreate} disabled={actionLoading || !form.titre}>{actionLoading ? <Loader2 className="h-4 w-4 animate-spin" /> : "Créer"}</Button></DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Edit Dialog */}
      <Dialog open={showEdit} onOpenChange={setShowEdit}>
        <DialogContent><DialogHeader><DialogTitle>Modifier le projet</DialogTitle><DialogDescription>Mettez à jour les informations</DialogDescription></DialogHeader>
          <div className="space-y-4">
            <div><Label>Titre</Label><Input value={form.titre} onChange={e => setForm({ ...form, titre: e.target.value })} /></div>
            <div><Label>Description</Label><Textarea value={form.description} onChange={e => setForm({ ...form, description: e.target.value })} /></div>
            <div><Label>Technologie</Label><Input value={form.technologie} onChange={e => setForm({ ...form, technologie: e.target.value })} /></div>
            <div><Label>Difficulté</Label><Select value={form.difficulte} onValueChange={v => setForm({ ...form, difficulte: v })}><SelectTrigger><SelectValue /></SelectTrigger><SelectContent><SelectItem value="debutant">Débutant</SelectItem><SelectItem value="intermediaire">Intermédiaire</SelectItem><SelectItem value="avance">Avancé</SelectItem></SelectContent></Select></div>
          </div>
          <DialogFooter><Button variant="outline" onClick={() => setShowEdit(false)}>Annuler</Button><Button onClick={handleEdit} disabled={actionLoading}>{actionLoading ? <Loader2 className="h-4 w-4 animate-spin" /> : "Enregistrer"}</Button></DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Detail + Tasks */}
      <Dialog open={showDetail} onOpenChange={v => { setShowDetail(v); if (!v) { setSelectedProjet(null); setTaches([]); setShowComments(null); } }}>
        <DialogContent className="max-w-2xl max-h-[80vh] overflow-y-auto">
          {selectedProjet && (<>
            <DialogHeader>
              <DialogTitle className="flex items-center gap-2">{selectedProjet.titre} <Badge variant={statutBadge(selectedProjet.statut)}>{selectedProjet.statut}</Badge></DialogTitle>
              <DialogDescription>{selectedProjet.description}</DialogDescription>
            </DialogHeader>
            {selectedProjet.membres && selectedProjet.membres.length > 0 && (
              <div><h3 className="text-sm font-semibold mb-2">Membres</h3><div className="flex flex-wrap gap-2">{selectedProjet.membres.map(m => (<div key={m.id} className="flex items-center gap-1 text-xs bg-muted px-2 py-1 rounded-full"><Avatar className="h-5 w-5"><AvatarFallback className="text-[10px]">{m.prenom[0]}{m.nom[0]}</AvatarFallback></Avatar>{m.prenom} {m.nom}</div>))}</div></div>
            )}
            <Separator />
            <div className="space-y-3">
              <div className="flex items-center justify-between">
                <h3 className="text-sm font-semibold flex items-center gap-1"><ListTodo className="h-4 w-4" /> Tâches ({taches.length})</h3>
                <Button size="sm" variant="outline" onClick={() => setShowTaskForm(true)}><Plus className="h-3 w-3 mr-1" /> Ajouter</Button>
              </div>
              {showTaskForm && (
                <Card className="p-3 space-y-2">
                  <Input placeholder="Titre de la tâche" value={taskForm.titre} onChange={e => setTaskForm({ ...taskForm, titre: e.target.value })} />
                  <Textarea placeholder="Description" value={taskForm.description} onChange={e => setTaskForm({ ...taskForm, description: e.target.value })} rows={2} />
                  <div className="flex gap-2">
                    <Select value={taskForm.priorite} onValueChange={v => setTaskForm({ ...taskForm, priorite: v })}><SelectTrigger className="w-32"><SelectValue /></SelectTrigger><SelectContent><SelectItem value="basse">Basse</SelectItem><SelectItem value="normale">Normale</SelectItem><SelectItem value="haute">Haute</SelectItem><SelectItem value="critique">Critique</SelectItem></SelectContent></Select>
                    <Button size="sm" onClick={handleCreateTask} disabled={actionLoading || !taskForm.titre}>Ajouter</Button>
                    <Button size="sm" variant="ghost" onClick={() => setShowTaskForm(false)}>Annuler</Button>
                  </div>
                </Card>
              )}
              {taches.length === 0 ? <p className="text-sm text-muted-foreground">Aucune tâche</p> : taches.map(t => (
                <Card key={t.id} className="p-3">
                  <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                      <Badge variant={t.statut === "terminee" ? "secondary" : "outline"} className="text-xs">{t.statut}</Badge>
                      <span className="text-sm font-medium">{t.titre}</span>
                      {t.priorite && <Badge variant="outline" className="text-xs">{t.priorite}</Badge>}
                    </div>
                    <div className="flex gap-1">
                      <Button size="sm" variant="ghost" onClick={() => { setShowComments(showComments === t.id ? null : t.id); if (showComments !== t.id) fetchCommentaires(selectedProjet.id, t.id); }}><MessageSquare className="h-3 w-3" /></Button>
                      {t.statut !== "terminee" && <Button size="sm" variant="ghost" onClick={() => handleCompleteTask(t.id)}><CheckCircle className="h-3 w-3" /></Button>}
                      <Button size="sm" variant="ghost" className="text-red-500" onClick={() => handleDeleteTask(t.id)}><Trash2 className="h-3 w-3" /></Button>
                    </div>
                  </div>
                  {t.description && <p className="text-xs text-muted-foreground mt-1">{t.description}</p>}
                  {t.assignee && <p className="text-xs mt-1">Assigné à : {t.assignee.prenom} {t.assignee.nom}</p>}
                  {showComments === t.id && (
                    <div className="mt-2 pl-4 border-l-2 space-y-2">
                      {(commentaires[t.id] || []).map(c => (<div key={c.id} className="text-xs"><span className="font-semibold">{c.utilisateur?.prenom} {c.utilisateur?.nom}</span><span className="text-muted-foreground ml-2">{c.created_at ? new Date(c.created_at).toLocaleString("fr-FR") : ""}</span><p>{c.contenu}</p></div>))}
                      <div className="flex gap-1"><Input placeholder="Commenter..." value={newComment} onChange={e => setNewComment(e.target.value)} className="text-xs h-7" onKeyDown={e => e.key === "Enter" && handleAddComment(t.id)} /><Button size="sm" variant="ghost" onClick={() => handleAddComment(t.id)}><Send className="h-3 w-3" /></Button></div>
                    </div>
                  )}
                </Card>
              ))}
            </div>
          </>)}
        </DialogContent>
      </Dialog>
    </div>
  );
}
