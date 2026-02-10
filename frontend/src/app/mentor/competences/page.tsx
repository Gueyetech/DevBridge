"use client";
import { useEffect, useState, useCallback } from "react";
import { apiClient } from "@/lib/api";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Separator } from "@/components/ui/separator";
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from "@/components/ui/dialog";
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from "@/components/ui/table";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import { Loader2, CheckCircle, Clock, BrainCircuit } from "lucide-react";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";

type CompetenceEnAttente = {
  id: number;
  etudiant: { id: number; prenom: string; nom: string };
  competence: { id: number; nom: string };
  niveau_maitrise?: number;
  created_at?: string;
};

export default function MentorCompetencesPage() {
  const [enAttente, setEnAttente] = useState<CompetenceEnAttente[]>([]);
  const [mesCompetences, setMesCompetences] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [actionLoading, setActionLoading] = useState(false);
  const [showValidate, setShowValidate] = useState(false);
  const [selectedItem, setSelectedItem] = useState<CompetenceEnAttente | null>(null);
  const [niveau, setNiveau] = useState("3");
  const [newComp, setNewComp] = useState("");
  const [tab, setTab] = useState("attente");

  const fetchAll = useCallback(async () => {
    setLoading(true);
    try {
      const [attenteRes, mesRes] = await Promise.all([
        apiClient.get<any>("/v1/mentor/mentorat/competences/en-attente").catch(() => ({ data: {} })),
        apiClient.get<any>("/v1/mentor/profil/competences").catch(() => ({ data: {} })),
      ]);
      const attenteData = attenteRes.data?.en_attente ?? attenteRes.data?.competences ?? attenteRes.data;
      const mesData = mesRes.data?.mes_competences ?? mesRes.data?.competences ?? mesRes.data;
      setEnAttente(Array.isArray(attenteData) ? attenteData : []);
      setMesCompetences(Array.isArray(mesData) ? mesData : []);
    } catch (e: any) { setError(e.message); }
    setLoading(false);
  }, []);

  useEffect(() => { fetchAll(); }, [fetchAll]);

  const handleValider = async () => {
    if (!selectedItem) return;
    setActionLoading(true);
    try {
      await apiClient.post(`/v1/mentor/mentorat/etudiants/${selectedItem.etudiant.id}/competences/valider`, {
        competence_id: selectedItem.competence.id,
        niveau_maitrise: parseInt(niveau),
      });
      setShowValidate(false);
      fetchAll();
    } catch (e: any) { setError(e.message); }
    setActionLoading(false);
  };

  const handleAddCompetence = async () => {
    if (!newComp.trim()) return;
    try {
      await apiClient.post("/v1/mentor/profil/competences", { nom: newComp });
      setNewComp("");
      fetchAll();
    } catch (e: any) { setError(e.message); }
  };

  if (loading) return <div className="py-12 text-center"><Loader2 className="h-8 w-8 animate-spin mx-auto" /></div>;

  return (
    <div className="space-y-6">
      <div><h1 className="text-2xl font-bold flex items-center gap-2"><BrainCircuit className="h-6 w-6" /> Compétences</h1><p className="text-muted-foreground">Validez les compétences étudiantes et gérez vos propres compétences</p></div>
      {error && <div className="text-red-600 text-sm bg-red-50 p-3 rounded-lg">{error}</div>}

      <div className="grid grid-cols-2 gap-4">
        <Card className="p-4 text-center"><p className="text-2xl font-bold">{enAttente.length}</p><p className="text-xs text-muted-foreground">En attente de validation</p></Card>
        <Card className="p-4 text-center"><p className="text-2xl font-bold">{mesCompetences.length}</p><p className="text-xs text-muted-foreground">Mes compétences</p></Card>
      </div>

      <Tabs value={tab} onValueChange={setTab}>
        <TabsList><TabsTrigger value="attente">En attente ({enAttente.length})</TabsTrigger><TabsTrigger value="mes">Mes compétences</TabsTrigger></TabsList>

        <TabsContent value="attente" className="mt-4">
          {enAttente.length === 0 ? <Card><CardContent className="py-12 text-center text-muted-foreground">Aucune compétence en attente de validation.</CardContent></Card> : (
            <Table>
              <TableHeader><TableRow><TableHead>Étudiant</TableHead><TableHead>Compétence</TableHead><TableHead>Niveau déclaré</TableHead><TableHead>Date</TableHead><TableHead>Action</TableHead></TableRow></TableHeader>
              <TableBody>
                {enAttente.map(item => (
                  <TableRow key={item.id}>
                    <TableCell><div className="flex items-center gap-2"><Avatar className="h-7 w-7"><AvatarFallback className="text-xs">{item.etudiant.prenom[0]}{item.etudiant.nom[0]}</AvatarFallback></Avatar>{item.etudiant.prenom} {item.etudiant.nom}</div></TableCell>
                    <TableCell><Badge variant="outline">{item.competence.nom}</Badge></TableCell>
                    <TableCell>{item.niveau_maitrise || "-"}</TableCell>
                    <TableCell className="text-xs text-muted-foreground">{item.created_at ? new Date(item.created_at).toLocaleDateString("fr-FR") : "-"}</TableCell>
                    <TableCell><Button size="sm" onClick={() => { setSelectedItem(item); setShowValidate(true); }}><CheckCircle className="h-3 w-3 mr-1" /> Valider</Button></TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </TabsContent>

        <TabsContent value="mes" className="mt-4 space-y-4">
          <div className="flex flex-wrap gap-2">
            {mesCompetences.map((c: any) => <Badge key={c.id} variant="secondary" className="px-3 py-1">{c.nom}</Badge>)}
          </div>
          <div className="flex gap-2">
            <Input placeholder="Ajouter une compétence..." value={newComp} onChange={e => setNewComp(e.target.value)} className="max-w-xs" onKeyDown={e => e.key === "Enter" && handleAddCompetence()} />
            <Button size="sm" onClick={handleAddCompetence} disabled={!newComp.trim()}>Ajouter</Button>
          </div>
        </TabsContent>
      </Tabs>

      {/* Validate Dialog */}
      <Dialog open={showValidate} onOpenChange={setShowValidate}>
        <DialogContent><DialogHeader><DialogTitle>Valider une compétence</DialogTitle><DialogDescription>Validez {selectedItem?.competence.nom} pour {selectedItem?.etudiant.prenom} {selectedItem?.etudiant.nom}</DialogDescription></DialogHeader>
          <div><label className="text-sm font-medium">Niveau de maîtrise</label>
            <Select value={niveau} onValueChange={setNiveau}><SelectTrigger><SelectValue /></SelectTrigger><SelectContent><SelectItem value="1">1 - Débutant</SelectItem><SelectItem value="2">2 - Basique</SelectItem><SelectItem value="3">3 - Intermédiaire</SelectItem><SelectItem value="4">4 - Avancé</SelectItem><SelectItem value="5">5 - Expert</SelectItem></SelectContent></Select>
          </div>
          <DialogFooter><Button variant="outline" onClick={() => setShowValidate(false)}>Annuler</Button><Button onClick={handleValider} disabled={actionLoading}>{actionLoading ? <Loader2 className="h-4 w-4 animate-spin" /> : "Valider"}</Button></DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
