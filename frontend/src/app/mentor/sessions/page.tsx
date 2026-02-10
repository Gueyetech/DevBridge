
"use client";
import { useEffect, useState } from "react";
import { apiClient } from "@/lib/api";
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from "@/components/ui/table";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Badge } from "@/components/ui/badge";
import { Separator } from "@/components/ui/separator";
import { Loader2, Video, XCircle, MessageCircle, PlusCircle, Edit2, Trash2, Eye } from "lucide-react";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";


type SessionMentorat = {
  id: string;
  titre: string;
  description?: string;
  date_debut: string;
  date_fin?: string;
  statut: string;
  lien_visioconference?: string;
  mentorat?: {
    etudiant?: {
      prenom: string;
      nom: string;
      profil?: { avatar?: string };
    };
  };
};

type SessionForm = {
  mentorat_id: string;
  titre: string;
  description?: string;
  date_debut: string;
  date_fin: string;
  lien_visioconference?: string;
};
type EtudiantMentore = {
  etudiant: {
    id: string;
    prenom: string;
    nom: string;
  };
  mentorat_depuis?: string;
  progression_moyenne: number;
  sessions_total: number;
  derniere_session?: any;
  derniere_activite?: any;
  mentorat_id?: string;
};


export default function MentorSessionsPage() {
  const [sessions, setSessions] = useState<SessionMentorat[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [actionLoading, setActionLoading] = useState<string | null>(null);
  const [feedbackMessage, setFeedbackMessage] = useState<string>("");

  // Pour la sélection d'étudiant mentoré
  const [etudiants, setEtudiants] = useState<EtudiantMentore[]>([]);

  // UI state for modals
  const [showCreate, setShowCreate] = useState(false);
  const [showEdit, setShowEdit] = useState<SessionMentorat | null>(null);
  const [showDetails, setShowDetails] = useState<SessionMentorat | null>(null);
  const [form, setForm] = useState<SessionForm>({ mentorat_id: "", titre: "", description: "", date_debut: "", date_fin: "" });
  // Charger les étudiants mentorés pour la création de session
  useEffect(() => {
    apiClient.get<any>("/v1/mentor/mentorat/etudiants").then(res => {
      setEtudiants(res.data?.etudiants || []);
    });
  }, []);

  const fetchSessions = () => {
    setIsLoading(true);
    apiClient.get<any>("/v1/mentor/mentorat/sessions")
      .then((res) => {
        const sessions = res?.data?.sessions || [];
        const sorted = sessions.slice().sort((a: any, b: any) => new Date(b.date_debut).getTime() - new Date(a.date_debut).getTime());
        setSessions(sorted);
        setIsLoading(false);
      })
      .catch(() => {
        setError("Erreur lors du chargement des sessions de mentorat.");
        setIsLoading(false);
      });
  };

  useEffect(() => {
    fetchSessions();
  }, []);
  // Création d'une session
  const handleCreate = async () => {
    setActionLoading("create");
    try {
      // mentorat_id obligatoire
      if (!form.mentorat_id) {
        setFeedbackMessage("Veuillez sélectionner un étudiant.");
        setActionLoading(null);
        return;
      }
      await apiClient.post(`/v1/mentor/mentorat/sessions/planifier/${form.mentorat_id}`, {
        titre: form.titre,
        description: form.description,
        date_debut: form.date_debut,
        date_fin: form.date_fin,
        lien_visioconference: form.lien_visioconference,
      });
      setShowCreate(false);
      setForm({ mentorat_id: "", titre: "", description: "", date_debut: "", date_fin: "" });
      setFeedbackMessage("Session créée !");
      fetchSessions();
    } catch {
      setFeedbackMessage("Erreur lors de la création.");
    } finally {
      setActionLoading(null);
      setTimeout(() => setFeedbackMessage(""), 2000);
    }
  };

  // Edition d'une session
  const handleEdit = async () => {
    if (!showEdit) return;
    setActionLoading("edit");
    try {
      await apiClient.put(`/v1/mentor/mentorat/sessions/${showEdit.id}`, {
        titre: form.titre,
        description: form.description,
        date_debut: form.date_debut,
        date_fin: form.date_fin,
        lien_visioconference: form.lien_visioconference,
      });
      setShowEdit(null);
      setForm({ mentorat_id: form.mentorat_id || "", titre: "", description: "", date_debut: "", date_fin: "" });
      setFeedbackMessage("Session modifiée !");
      fetchSessions();
    } catch {
      setFeedbackMessage("Erreur lors de la modification.");
    } finally {
      setActionLoading(null);
      setTimeout(() => setFeedbackMessage(""), 2000);
    }
  };

  // Suppression d'une session
  const handleDelete = async (id: string) => {
    if (!window.confirm("Supprimer cette session ?")) return;
    setActionLoading(id);
    try {
      await apiClient.delete(`/v1/mentor/mentorat/sessions/${id}`);
      setFeedbackMessage("Session supprimée !");
      fetchSessions();
    } catch {
      setFeedbackMessage("Erreur lors de la suppression.");
    } finally {
      setActionLoading(null);
      setTimeout(() => setFeedbackMessage(""), 2000);
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Sessions de mentorat</h1>
          <p className="text-muted-foreground">Toutes vos sessions planifiées, en cours ou terminées</p>
        </div>
        <Button variant="default" onClick={() => { setShowCreate(true); setForm({ mentorat_id: "", titre: "", description: "", date_debut: "", date_fin: "" }); }}>
          <PlusCircle className="w-4 h-4 mr-2" /> Nouvelle session
        </Button>
      </div>
      <Separator className="my-6" />
      {isLoading ? (
        <div className="flex items-center justify-center min-h-40"><Loader2 className="h-8 w-8 animate-spin" /></div>
      ) : error ? (
        <div className="text-red-600 py-8 text-center">{error}</div>
      ) : sessions.length === 0 ? (
        <div className="text-muted-foreground py-8 text-center">Aucune session de mentorat pour le moment.</div>
      ) : (
        <div className="overflow-x-auto">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Étudiant</TableHead>
                <TableHead>Titre</TableHead>
                <TableHead>Date & heure</TableHead>
                <TableHead>Fin</TableHead>
                <TableHead>Statut</TableHead>
                <TableHead>Lien visio</TableHead>
                <TableHead>Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {sessions.map((session) => (
                <TableRow key={session.id}>
                  <TableCell className="whitespace-nowrap">
                    <div className="flex items-center gap-2">
                      <Avatar className="h-8 w-8">
                        <AvatarImage src={session.mentorat?.etudiant?.profil?.avatar || undefined} />
                        <AvatarFallback>
                          {session.mentorat?.etudiant?.prenom?.charAt(0)}
                          {session.mentorat?.etudiant?.nom?.charAt(0)}
                        </AvatarFallback>
                      </Avatar>
                      <span className="font-semibold">
                        {session.mentorat?.etudiant?.prenom} {session.mentorat?.etudiant?.nom}
                      </span>
                    </div>
                  </TableCell>
                  <TableCell>{session.titre}</TableCell>
                  <TableCell>
                    {session.date_debut ? new Date(session.date_debut).toLocaleString("fr-FR", { dateStyle: "short", timeStyle: "short" }) : "-"}
                  </TableCell>
                  <TableCell>
                    {session.date_fin ? new Date(session.date_fin).toLocaleString("fr-FR", { dateStyle: "short", timeStyle: "short" }) : "-"}
                  </TableCell>
                  <TableCell>
                    <Badge variant={
                      session.statut === "termine" ? "secondary" :
                      session.statut === "en_cours" ? "default" :
                      session.statut === "planifie" ? "outline" :
                      session.statut === "annule" ? "destructive" : "default"
                    } className="uppercase tracking-wide">
                      {session.statut.replace("_", " ").toUpperCase()}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    {session.lien_visioconference ? (
                      <a href={session.lien_visioconference} target="_blank" rel="noopener noreferrer" className="flex items-center gap-1 text-blue-600 hover:underline">
                        <Video className="w-4 h-4" />
                        <span>Lien</span>
                      </a>
                    ) : <span className="text-muted-foreground text-xs">-</span>}
                  </TableCell>
                  <TableCell>
                    <div className="flex gap-2 items-center">
                      <Button size="icon" variant="ghost" title="Voir détails" onClick={() => setShowDetails(session)}><Eye className="w-4 h-4" /></Button>
                      {session.statut === "planifie" && (
                        <Button size="icon" variant="ghost" title="Modifier" onClick={() => { setShowEdit(session); setForm({
                          mentorat_id: (session as any).mentorat_id || "",
                          titre: session.titre,
                          description: session.description || "",
                          date_debut: session.date_debut?.slice(0, 16),
                          date_fin: session.date_fin?.slice(0, 16) || ""
                        }); }}><Edit2 className="w-4 h-4" /></Button>
                      )}
                      <Button size="icon" variant="ghost" title="Supprimer" disabled={actionLoading === session.id} onClick={() => handleDelete(session.id)}><Trash2 className="w-4 h-4" /></Button>
                      {session.statut === "planifie" && (
                        <Button size="icon" variant="ghost" title="Annuler" disabled={actionLoading === session.id} onClick={async () => {
                          if (!window.confirm("Annuler cette session ?")) return;
                          setActionLoading(session.id);
                          try {
                            await apiClient.post(`/v1/mentor/mentorat/sessions/${session.id}/annuler`);
                            setSessions((prev) => prev.map(s => s.id === session.id ? { ...s, statut: "annule" } : s));
                          } catch (e) {
                            alert("Erreur lors de l'annulation.");
                          } finally {
                            setActionLoading(null);
                          }
                        }}><XCircle className="w-4 h-4" /></Button>
                      )}
                      {session.statut === "termine" && (
                        <Button size="icon" variant="ghost" title="Feedback" disabled={actionLoading === session.id} onClick={async () => {
                          const feedback = window.prompt("Feedback pour l'étudiant :");
                          if (!feedback) return;
                          setActionLoading(session.id);
                          try {
                            await apiClient.post(`/v1/mentor/mentorat/sessions/${session.id}/feedback`, { feedback });
                            setFeedbackMessage("Feedback envoyé !");
                            setTimeout(() => setFeedbackMessage(""), 2000);
                          } catch (e) {
                            alert("Erreur lors de l'envoi du feedback.");
                          } finally {
                            setActionLoading(null);
                          }
                        }}><MessageCircle className="w-4 h-4" /></Button>
                      )}
                    </div>
                        {/* Modale création session */}
                        <Dialog open={showCreate} onOpenChange={setShowCreate}>
                          <DialogContent>
                            <DialogHeader><DialogTitle>Nouvelle session</DialogTitle></DialogHeader>
                            <div className="space-y-2">
                              <select className="input w-full" value={form.mentorat_id} onChange={e => setForm(f => ({ ...f, mentorat_id: e.target.value }))}>
                                <option value="">Sélectionner un étudiant mentoré</option>
                                {etudiants.map((item) => (
                                  <option key={item.etudiant.id} value={item.mentorat_id || item.etudiant.id}>
                                    {item.etudiant.prenom} {item.etudiant.nom}
                                  </option>
                                ))}
                              </select>
                              <input className="input w-full" placeholder="Titre" value={form.titre} onChange={e => setForm(f => ({ ...f, titre: e.target.value }))} />
                              <textarea className="input w-full" placeholder="Description" value={form.description} onChange={e => setForm(f => ({ ...f, description: e.target.value }))} />
                              <input className="input w-full" type="datetime-local" value={form.date_debut} onChange={e => setForm(f => ({ ...f, date_debut: e.target.value }))} />
                              <input className="input w-full" type="datetime-local" value={form.date_fin} onChange={e => setForm(f => ({ ...f, date_fin: e.target.value }))} />
                              <input className="input w-full" placeholder="Lien visioconférence" value={form.lien_visioconference || ""} onChange={e => setForm(f => ({ ...f, lien_visioconference: e.target.value }))} />
                            </div>
                            <DialogFooter>
                              <Button onClick={handleCreate} disabled={actionLoading === "create"}>Créer</Button>
                              <Button variant="ghost" onClick={() => setShowCreate(false)}>Annuler</Button>
                            </DialogFooter>
                          </DialogContent>
                        </Dialog>

                        {/* Modale édition session */}
                        <Dialog open={!!showEdit} onOpenChange={v => { if (!v) setShowEdit(null); }}>
                          <DialogContent>
                            <DialogHeader><DialogTitle>Modifier la session</DialogTitle></DialogHeader>
                            <div className="space-y-2">
                              <input className="input w-full" placeholder="Titre" value={form.titre} onChange={e => setForm(f => ({ ...f, titre: e.target.value }))} />
                              <textarea className="input w-full" placeholder="Description" value={form.description} onChange={e => setForm(f => ({ ...f, description: e.target.value }))} />
                              <input className="input w-full" type="datetime-local" value={form.date_debut} onChange={e => setForm(f => ({ ...f, date_debut: e.target.value }))} />
                              <input className="input w-full" type="datetime-local" value={form.date_fin} onChange={e => setForm(f => ({ ...f, date_fin: e.target.value }))} />
                              <input className="input w-full" placeholder="Lien visioconférence" value={form.lien_visioconference || ""} onChange={e => setForm(f => ({ ...f, lien_visioconference: e.target.value }))} />
                            </div>
                            <DialogFooter>
                              <Button onClick={handleEdit} disabled={actionLoading === "edit"}>Enregistrer</Button>
                              <Button variant="ghost" onClick={() => setShowEdit(null)}>Annuler</Button>
                            </DialogFooter>
                          </DialogContent>
                        </Dialog>

                        {/* Modale détails session */}
                        <Dialog open={!!showDetails} onOpenChange={v => { if (!v) setShowDetails(null); }}>
                          <DialogContent>
                            <DialogHeader><DialogTitle>Détails de la session</DialogTitle></DialogHeader>
                            {showDetails && (
                              <div className="space-y-2">
                                <div><b>Titre :</b> {showDetails.titre}</div>
                                <div><b>Description :</b> {showDetails.description}</div>
                                <div><b>Date début :</b> {showDetails.date_debut ? new Date(showDetails.date_debut).toLocaleString("fr-FR") : "-"}</div>
                                <div><b>Date fin :</b> {showDetails.date_fin ? new Date(showDetails.date_fin).toLocaleString("fr-FR") : "-"}</div>
                                <div><b>Statut :</b> {showDetails.statut}</div>
                                <div><b>Lien visio :</b> {showDetails.lien_visioconference || "-"}</div>
                                <div><b>Étudiant :</b> {showDetails.mentorat?.etudiant?.prenom} {showDetails.mentorat?.etudiant?.nom}</div>
                              </div>
                            )}
                            <DialogFooter>
                              <Button variant="ghost" onClick={() => setShowDetails(null)}>Fermer</Button>
                            </DialogFooter>
                          </DialogContent>
                        </Dialog>
                  </TableCell>
                      {/* Message de feedback global */}
                      {feedbackMessage && (
                        <div className="fixed bottom-4 right-4 bg-green-600 text-white px-4 py-2 rounded shadow-lg z-50">{feedbackMessage}</div>
                      )}
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </div>
      )}
    </div>
  );
}
