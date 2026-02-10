"use client";
import { useEffect, useState, useCallback } from "react";
import { apiClient } from "@/lib/api";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Separator } from "@/components/ui/separator";
import { Textarea } from "@/components/ui/textarea";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from "@/components/ui/dialog";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Loader2, Users, Calendar, MessageSquare, Star, Send, UserPlus } from "lucide-react";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";

type Mentor = {
  id: number; prenom: string; nom: string; email?: string;
  avatar?: string; specialites?: string[]; bio?: string;
  competences?: { id: number; nom: string }[];
  disponible?: boolean;
};

type DemandeMentorat = {
  id: number; statut: string; mentor?: Mentor; message?: string;
  created_at?: string;
};

type SessionMentorat = {
  id: number; date_session?: string; duree?: number; type_session?: string;
  statut: string; sujet?: string; notes?: string;
  mentor?: { prenom: string; nom: string };
};

export default function EtudiantMentoratPage() {
  const [mentors, setMentors] = useState<Mentor[]>([]);
  const [demandes, setDemandes] = useState<DemandeMentorat[]>([]);
  const [sessions, setSessions] = useState<SessionMentorat[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [tab, setTab] = useState("mentors");
  const [showRequest, setShowRequest] = useState(false);
  const [showFeedback, setShowFeedback] = useState(false);
  const [selectedMentor, setSelectedMentor] = useState<Mentor | null>(null);
  const [selectedSession, setSelectedSession] = useState<SessionMentorat | null>(null);
  const [message, setMessage] = useState("");
  const [feedback, setFeedback] = useState({ note: 5, commentaire: "" });
  const [actionLoading, setActionLoading] = useState(false);

  const fetchAll = useCallback(async () => {
    setLoading(true);
    try {
      const [mentorsRes, demandesRes, sessionsRes] = await Promise.all([
        apiClient.get<any>("/v1/etudiant/mentorat/mentors").catch(() => ({ data: {} })),
        apiClient.get<any>("/v1/etudiant/mentorat/demandes").catch(() => ({ data: {} })),
        apiClient.get<any>("/v1/etudiant/mentorat/sessions").catch(() => ({ data: {} })),
      ]);
      setMentors(mentorsRes.data?.mentors || mentorsRes.data || []);
      setDemandes(demandesRes.data?.demandes || demandesRes.data || []);
      setSessions(sessionsRes.data?.sessions || sessionsRes.data || []);
    } catch (e: any) { setError(e.message); }
    setLoading(false);
  }, []);

  useEffect(() => { fetchAll(); }, [fetchAll]);

  const handleDemander = async () => {
    if (!selectedMentor) return;
    setActionLoading(true);
    try {
      await apiClient.post(`/v1/etudiant/mentorat/demander/${selectedMentor.id}`, { message });
      setShowRequest(false);
      setMessage("");
      fetchAll();
    } catch (e: any) { setError(e.message); }
    setActionLoading(false);
  };

  const handleFeedback = async () => {
    if (!selectedSession) return;
    setActionLoading(true);
    try {
      await apiClient.post(`/v1/etudiant/mentorat/sessions/${selectedSession.id}/feedback`, feedback);
      setShowFeedback(false);
      setFeedback({ note: 5, commentaire: "" });
      fetchAll();
    } catch (e: any) { setError(e.message); }
    setActionLoading(false);
  };

  if (loading) return <div className="py-12 text-center"><Loader2 className="h-8 w-8 animate-spin mx-auto" /></div>;

  return (
    <div className="space-y-6">
      <div><h1 className="text-2xl font-bold flex items-center gap-2"><Users className="h-6 w-6" /> Mentorat</h1><p className="text-muted-foreground">Trouvez un mentor et suivez vos sessions</p></div>
      {error && <div className="text-red-600 text-sm bg-red-50 p-3 rounded-lg">{error}</div>}

      {/* Stats */}
      <div className="grid grid-cols-3 gap-4">
        <Card className="p-4 text-center"><p className="text-2xl font-bold">{demandes.length}</p><p className="text-xs text-muted-foreground">Demandes</p></Card>
        <Card className="p-4 text-center"><p className="text-2xl font-bold">{sessions.length}</p><p className="text-xs text-muted-foreground">Sessions</p></Card>
        <Card className="p-4 text-center"><p className="text-2xl font-bold">{demandes.filter(d => d.statut === "acceptee").length}</p><p className="text-xs text-muted-foreground">Acceptées</p></Card>
      </div>

      <Tabs value={tab} onValueChange={setTab}>
        <TabsList>
          <TabsTrigger value="mentors">Mentors disponibles ({mentors.length})</TabsTrigger>
          <TabsTrigger value="demandes">Mes demandes ({demandes.length})</TabsTrigger>
          <TabsTrigger value="sessions">Sessions ({sessions.length})</TabsTrigger>
        </TabsList>

        <TabsContent value="mentors" className="mt-4">
          {mentors.length === 0 ? <Card><CardContent className="py-12 text-center text-muted-foreground">Aucun mentor disponible.</CardContent></Card> : (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              {mentors.map(m => (
                <Card key={m.id}>
                  <CardContent className="p-4 space-y-3">
                    <div className="flex items-center gap-3">
                      <Avatar className="h-12 w-12"><AvatarImage src={m.avatar} /><AvatarFallback>{m.prenom?.[0]}{m.nom?.[0]}</AvatarFallback></Avatar>
                      <div><h3 className="font-semibold">{m.prenom} {m.nom}</h3>{m.bio && <p className="text-xs text-muted-foreground line-clamp-2">{m.bio}</p>}</div>
                    </div>
                    {m.competences && m.competences.length > 0 && (
                      <div className="flex flex-wrap gap-1">{m.competences.map(c => <Badge key={c.id} variant="outline" className="text-xs">{c.nom}</Badge>)}</div>
                    )}
                    {m.specialites && m.specialites.length > 0 && (
                      <div className="flex flex-wrap gap-1">{m.specialites.map((s, i) => <Badge key={i} variant="secondary" className="text-xs">{s}</Badge>)}</div>
                    )}
                    <Button size="sm" className="w-full" onClick={() => { setSelectedMentor(m); setShowRequest(true); }}><UserPlus className="h-3 w-3 mr-1" /> Demander un mentorat</Button>
                  </CardContent>
                </Card>
              ))}
            </div>
          )}
        </TabsContent>

        <TabsContent value="demandes" className="mt-4 space-y-3">
          {demandes.length === 0 ? <Card><CardContent className="py-12 text-center text-muted-foreground">Aucune demande de mentorat.</CardContent></Card> : demandes.map(d => (
            <Card key={d.id} className="p-4">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <Avatar className="h-10 w-10"><AvatarFallback>{d.mentor?.prenom?.[0]}{d.mentor?.nom?.[0]}</AvatarFallback></Avatar>
                  <div><h3 className="font-semibold">Mentor : {d.mentor?.prenom} {d.mentor?.nom}</h3><p className="text-xs text-muted-foreground">{d.created_at ? new Date(d.created_at).toLocaleDateString("fr-FR") : ""}</p></div>
                </div>
                <Badge variant={d.statut === "acceptee" ? "default" : d.statut === "refusee" ? "destructive" : "outline"}>{d.statut}</Badge>
              </div>
              {d.message && <p className="text-sm text-muted-foreground mt-2">{d.message}</p>}
            </Card>
          ))}
        </TabsContent>

        <TabsContent value="sessions" className="mt-4 space-y-3">
          {sessions.length === 0 ? <Card><CardContent className="py-12 text-center text-muted-foreground">Aucune session planifiée.</CardContent></Card> : sessions.map(s => (
            <Card key={s.id} className="p-4">
              <div className="flex items-center justify-between">
                <div>
                  <h3 className="font-semibold">{s.sujet || "Session de mentorat"}</h3>
                  <div className="flex gap-2 mt-1 text-xs text-muted-foreground">
                    {s.date_session && <span className="flex items-center gap-1"><Calendar className="h-3 w-3" /> {new Date(s.date_session).toLocaleString("fr-FR")}</span>}
                    {s.duree && <span>{s.duree} min</span>}
                    {s.type_session && <Badge variant="outline">{s.type_session}</Badge>}
                  </div>
                  {s.mentor && <p className="text-xs mt-1">Avec {s.mentor.prenom} {s.mentor.nom}</p>}
                </div>
                <div className="flex items-center gap-2">
                  <Badge variant={s.statut === "terminee" ? "secondary" : s.statut === "annulee" ? "destructive" : "default"}>{s.statut}</Badge>
                  {s.statut === "terminee" && <Button size="sm" variant="outline" onClick={() => { setSelectedSession(s); setShowFeedback(true); }}><Star className="h-3 w-3 mr-1" /> Feedback</Button>}
                </div>
              </div>
              {s.notes && <p className="text-sm text-muted-foreground mt-2">{s.notes}</p>}
            </Card>
          ))}
        </TabsContent>
      </Tabs>

      {/* Request Dialog */}
      <Dialog open={showRequest} onOpenChange={v => { setShowRequest(v); if (!v) setMessage(""); }}>
        <DialogContent><DialogHeader><DialogTitle>Demander un mentorat</DialogTitle><DialogDescription>Envoyer une demande à {selectedMentor?.prenom} {selectedMentor?.nom}</DialogDescription></DialogHeader>
          <Textarea placeholder="Message pour le mentor (optionnel)..." value={message} onChange={e => setMessage(e.target.value)} rows={4} />
          <DialogFooter><Button variant="outline" onClick={() => setShowRequest(false)}>Annuler</Button><Button onClick={handleDemander} disabled={actionLoading}>{actionLoading ? <Loader2 className="h-4 w-4 animate-spin" /> : <><Send className="h-4 w-4 mr-2" /> Envoyer</>}</Button></DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Feedback Dialog */}
      <Dialog open={showFeedback} onOpenChange={v => { setShowFeedback(v); if (!v) setFeedback({ note: 5, commentaire: "" }); }}>
        <DialogContent><DialogHeader><DialogTitle>Donner un feedback</DialogTitle><DialogDescription>Évaluez votre session</DialogDescription></DialogHeader>
          <div className="space-y-4">
            <div><label className="text-sm font-medium">Note</label><div className="flex gap-1 mt-1">{[1,2,3,4,5].map(n => (<Button key={n} size="sm" variant={feedback.note >= n ? "default" : "outline"} onClick={() => setFeedback({ ...feedback, note: n })}><Star className="h-4 w-4" /></Button>))}</div></div>
            <Textarea placeholder="Commentaire..." value={feedback.commentaire} onChange={e => setFeedback({ ...feedback, commentaire: e.target.value })} rows={3} />
          </div>
          <DialogFooter><Button variant="outline" onClick={() => setShowFeedback(false)}>Annuler</Button><Button onClick={handleFeedback} disabled={actionLoading}>{actionLoading ? <Loader2 className="h-4 w-4 animate-spin" /> : "Envoyer"}</Button></DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
