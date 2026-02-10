"use client";
import { useEffect, useState, useCallback } from "react";
import { apiClient } from "@/lib/api";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Separator } from "@/components/ui/separator";
import { Textarea } from "@/components/ui/textarea";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from "@/components/ui/dialog";
import { Loader2, Trophy, Swords, Send, Medal, Eye } from "lucide-react";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";

type Defi = {
  id: number; titre: string; description?: string; difficulte?: string;
  points?: number; date_limite?: string; statut?: string;
  nombre_participants?: number; categorie?: string;
};

type Participation = {
  id: number; defi_id: number; statut: string; score?: number;
  solution?: string; defi?: Defi; created_at?: string;
};

type Classement = {
  position: number; utilisateur: { id: number; prenom: string; nom: string };
  score: number; temps?: string;
};

export default function EtudiantDefisPage() {
  const [defis, setDefis] = useState<Defi[]>([]);
  const [participations, setParticipations] = useState<Participation[]>([]);
  const [classement, setClassement] = useState<Classement[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [selectedDefi, setSelectedDefi] = useState<Defi | null>(null);
  const [showSubmit, setShowSubmit] = useState(false);
  const [solution, setSolution] = useState("");
  const [actionLoading, setActionLoading] = useState(false);
  const [showClassement, setShowClassement] = useState(false);
  const [tab, setTab] = useState("disponibles");

  const fetchAll = useCallback(async () => {
    setLoading(true);
    try {
      const [defisRes, partRes, classRes] = await Promise.all([
        apiClient.get<any>("/v1/etudiant/defis"),
        apiClient.get<any>("/v1/etudiant/defis/participations").catch(() => ({ data: {} })),
        apiClient.get<any>("/v1/etudiant/defis/classement").catch(() => ({ data: {} })),
      ]);
      setDefis(defisRes.data?.defis || defisRes.data || []);
      setParticipations(partRes.data?.participations || partRes.data || []);
      setClassement(classRes.data?.classement || classRes.data || []);
    } catch (e: any) { setError(e.message); }
    setLoading(false);
  }, []);

  useEffect(() => { fetchAll(); }, [fetchAll]);

  const handleParticiper = async (defiId: number) => {
    setActionLoading(true);
    try {
      await apiClient.post(`/v1/etudiant/defis/${defiId}/participer`);
      fetchAll();
    } catch (e: any) { setError(e.message); }
    setActionLoading(false);
  };

  const handleSoumettre = async () => {
    if (!selectedDefi) return;
    setActionLoading(true);
    try {
      await apiClient.post(`/v1/etudiant/defis/${selectedDefi.id}/soumettre`, { solution });
      setShowSubmit(false);
      setSolution("");
      fetchAll();
    } catch (e: any) { setError(e.message); }
    setActionLoading(false);
  };

  const isParticipating = (defiId: number) => participations.some(p => p.defi_id === defiId);

  if (loading) return <div className="py-12 text-center"><Loader2 className="h-8 w-8 animate-spin mx-auto" /></div>;

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div><h1 className="text-2xl font-bold flex items-center gap-2"><Swords className="h-6 w-6" /> Défis</h1><p className="text-muted-foreground">Relevez des défis et grimpez dans le classement</p></div>
        <Button variant="outline" onClick={() => setShowClassement(true)}><Trophy className="h-4 w-4 mr-2" /> Classement</Button>
      </div>
      {error && <div className="text-red-600 text-sm bg-red-50 p-3 rounded-lg">{error}</div>}

      <Tabs value={tab} onValueChange={setTab}>
        <TabsList><TabsTrigger value="disponibles">Défis disponibles</TabsTrigger><TabsTrigger value="participations">Mes participations ({participations.length})</TabsTrigger></TabsList>

        <TabsContent value="disponibles" className="mt-4">
          {defis.length === 0 ? <Card><CardContent className="py-12 text-center text-muted-foreground">Aucun défi disponible.</CardContent></Card> : (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              {defis.map(d => (
                <Card key={d.id}>
                  <CardHeader className="pb-2">
                    <div className="flex justify-between items-start"><CardTitle className="text-lg">{d.titre}</CardTitle>{d.points && <Badge variant="secondary">{d.points} pts</Badge>}</div>
                    <CardDescription className="line-clamp-3">{d.description}</CardDescription>
                  </CardHeader>
                  <CardContent className="space-y-3">
                    <div className="flex flex-wrap gap-2 text-xs">
                      {d.difficulte && <Badge variant="outline">{d.difficulte}</Badge>}
                      {d.categorie && <Badge variant="outline">{d.categorie}</Badge>}
                      {d.nombre_participants !== undefined && <span className="text-muted-foreground">{d.nombre_participants} participants</span>}
                      {d.date_limite && <span className="text-muted-foreground">Limite : {new Date(d.date_limite).toLocaleDateString("fr-FR")}</span>}
                    </div>
                    <div className="flex gap-2">
                      {isParticipating(d.id) ? (
                        <Button size="sm" onClick={() => { setSelectedDefi(d); setShowSubmit(true); }}><Send className="h-3 w-3 mr-1" /> Soumettre</Button>
                      ) : (
                        <Button size="sm" onClick={() => handleParticiper(d.id)} disabled={actionLoading}><Swords className="h-3 w-3 mr-1" /> Participer</Button>
                      )}
                      <Button size="sm" variant="ghost" onClick={() => setSelectedDefi(d)}><Eye className="h-3 w-3 mr-1" /> Détail</Button>
                    </div>
                  </CardContent>
                </Card>
              ))}
            </div>
          )}
        </TabsContent>

        <TabsContent value="participations" className="mt-4">
          {participations.length === 0 ? <Card><CardContent className="py-12 text-center text-muted-foreground">Aucune participation.</CardContent></Card> : (
            <div className="space-y-3">
              {participations.map(p => (
                <Card key={p.id} className="p-4">
                  <div className="flex items-center justify-between">
                    <div>
                      <h3 className="font-semibold">{p.defi?.titre || `Défi #${p.defi_id}`}</h3>
                      <div className="flex gap-2 mt-1"><Badge variant={p.statut === "soumis" ? "default" : p.statut === "en_cours" ? "secondary" : "outline"}>{p.statut}</Badge>{p.score !== undefined && <span className="text-sm text-muted-foreground">Score : {p.score}</span>}</div>
                    </div>
                    {p.statut === "en_cours" && <Button size="sm" onClick={() => { setSelectedDefi(p.defi || { id: p.defi_id } as Defi); setShowSubmit(true); }}><Send className="h-3 w-3 mr-1" /> Soumettre</Button>}
                  </div>
                </Card>
              ))}
            </div>
          )}
        </TabsContent>
      </Tabs>

      {/* Detail Dialog */}
      {selectedDefi && !showSubmit && (
        <Dialog open onOpenChange={() => setSelectedDefi(null)}>
          <DialogContent><DialogHeader><DialogTitle>{selectedDefi.titre}</DialogTitle><DialogDescription>{selectedDefi.description}</DialogDescription></DialogHeader>
            <div className="space-y-2 text-sm">
              {selectedDefi.difficulte && <div><span className="font-semibold">Difficulté :</span> {selectedDefi.difficulte}</div>}
              {selectedDefi.points && <div><span className="font-semibold">Points :</span> {selectedDefi.points}</div>}
              {selectedDefi.date_limite && <div><span className="font-semibold">Date limite :</span> {new Date(selectedDefi.date_limite).toLocaleDateString("fr-FR")}</div>}
            </div>
            <DialogFooter>
              <Button variant="outline" onClick={() => setSelectedDefi(null)}>Fermer</Button>
              {!isParticipating(selectedDefi.id) && <Button onClick={() => handleParticiper(selectedDefi.id)} disabled={actionLoading}>Participer</Button>}
              {isParticipating(selectedDefi.id) && <Button onClick={() => setShowSubmit(true)}>Soumettre solution</Button>}
            </DialogFooter>
          </DialogContent>
        </Dialog>
      )}

      {/* Submit Dialog */}
      <Dialog open={showSubmit} onOpenChange={v => { setShowSubmit(v); if (!v) setSolution(""); }}>
        <DialogContent><DialogHeader><DialogTitle>Soumettre une solution</DialogTitle><DialogDescription>{selectedDefi?.titre}</DialogDescription></DialogHeader>
          <Textarea placeholder="Collez votre code ou solution ici..." value={solution} onChange={e => setSolution(e.target.value)} rows={10} className="font-mono text-sm" />
          <DialogFooter><Button variant="outline" onClick={() => setShowSubmit(false)}>Annuler</Button><Button onClick={handleSoumettre} disabled={actionLoading || !solution.trim()}>{actionLoading ? <Loader2 className="h-4 w-4 animate-spin" /> : "Soumettre"}</Button></DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Classement Dialog */}
      <Dialog open={showClassement} onOpenChange={setShowClassement}>
        <DialogContent><DialogHeader><DialogTitle className="flex items-center gap-2"><Trophy className="h-5 w-5 text-yellow-500" /> Classement général</DialogTitle></DialogHeader>
          {classement.length === 0 ? <p className="text-muted-foreground text-center py-8">Aucun classement disponible.</p> : (
            <div className="space-y-2">
              {classement.map((c, i) => (
                <div key={i} className="flex items-center gap-3 p-2 rounded-lg hover:bg-muted">
                  <span className="text-lg font-bold w-8 text-center">{c.position || i + 1}{(c.position || i + 1) <= 3 && <Medal className="h-4 w-4 inline ml-1 text-yellow-500" />}</span>
                  <span className="flex-1">{c.utilisateur?.prenom} {c.utilisateur?.nom}</span>
                  <Badge variant="secondary">{c.score} pts</Badge>
                </div>
              ))}
            </div>
          )}
        </DialogContent>
      </Dialog>
    </div>
  );
}
