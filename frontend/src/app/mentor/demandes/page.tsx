"use client";
import { useEffect, useState } from "react";
import { apiClient } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Separator } from "@/components/ui/separator";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Loader2, History, FileText, UserCheck, UserX, FileEdit, MoreHorizontal } from "lucide-react";
import { Tooltip, TooltipProvider, TooltipTrigger, TooltipContent } from "@/components/ui/tooltip";
import { DropdownMenu, DropdownMenuTrigger, DropdownMenuContent, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuItem } from "@/components/ui/dropdown-menu";
import { Table, TableHeader, TableBody, TableRow, TableCell, TableHead, TableCaption } from "@/components/ui/table";
import { Card, CardContent } from "@/components/ui/card";
import { Select, SelectTrigger, SelectContent, SelectItem, SelectValue } from "@/components/ui/select";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";

type Revision = {
  id: string;
  mentor_id: string;
  statut: string;
  commentaires?: any[];
  points_positifs?: string[];
  points_amelioration?: string[];
  note_generale?: number;
  accepte_a?: string;
  refuse_a?: string;
  termine_a?: string;
};

type Demande = {
  id: string;
  titre: string;
  description: string;
  statut: string;
  urgence: string;
  etudiant: { id: string; prenom: string; nom: string; profil?: any };
  projet?: { id: string; titre: string };
  tache?: { id: string; titre: string };
  revisions?: Revision[];
  created_at?: string;
};


export default function MentorDemandesPage() {
  const [demandes, setDemandes] = useState<Demande[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [selected, setSelected] = useState<Demande | null>(null);
  // Filtres
  const [statutFilter, setStatutFilter] = useState<string>("all");
  const [urgenceFilter, setUrgenceFilter] = useState<string>("all");
  const [search, setSearch] = useState<string>("");
  // Pagination
  const [page, setPage] = useState(1);
  const pageSize = 5;
  // Liste unique des projets pour le filtre
  // Application des filtres
  const filteredDemandes = demandes.filter(d =>
    (statutFilter === "all" ? true : d.statut === statutFilter) &&
    (urgenceFilter === "all" ? true : d.urgence === urgenceFilter) &&
    (search ? (
      d.titre.toLowerCase().includes(search.toLowerCase()) ||
      d.etudiant.prenom.toLowerCase().includes(search.toLowerCase()) ||
      d.etudiant.nom.toLowerCase().includes(search.toLowerCase())
    ) : true)
  );
  const totalPages = Math.ceil(filteredDemandes.length / pageSize);
  const demandesPage = filteredDemandes.slice((page - 1) * pageSize, page * pageSize);
  const [actionLoading, setActionLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [showHistory, setShowHistory] = useState(false);
  const [history, setHistory] = useState<Revision[]>([]);
  const [historyLoading, setHistoryLoading] = useState(false);
  // Charger l'historique des révisions
  const fetchHistory = async () => {
    setHistoryLoading(true);
    try {
      const res: any = await apiClient.get("/v1/mentor/revision-code/historique");
      setHistory((res.data?.revisions && Array.isArray(res.data.revisions)) ? res.data.revisions : []);
    } catch {
      setHistory([]);
    }
    setHistoryLoading(false);
  };
  // Soumettre une révision (exemple simplifié)
  const handleReviser = async (demandeId: string) => {
    setActionLoading(true);
    setError(null);
    try {
      await apiClient.post(`/v1/mentor/revision-code/demandes/${demandeId}/reviser`, {
        commentaires: [{ ligne: 1, fichier: "index.js", contenu: "Bien structuré.", type: "suggestion" }],
        points_positifs: ["Bonne organisation"],
        points_amelioration: ["Optimiser la logique"],
        note_generale: 4,
      });
      setDemandes((prev) => prev.map(d => d.id === demandeId ? { ...d, statut: "terminee" } : d));
      if (selected && selected.id === demandeId) setSelected({ ...selected, statut: "terminee" });
    } catch (e: any) {
      setError(e.message || "Erreur lors de la révision.");
    }
    setActionLoading(false);
  };

  useEffect(() => {
    apiClient.get("/v1/mentor/revision-code/demandes")
      .then((res: any) => {
        // Correction : la réponse est dans res.data.demandes
        setDemandes(res.data?.demandes || []);
        setIsLoading(false);
      })
      .catch(() => {
        setError("Erreur lors du chargement des demandes.");
        setIsLoading(false);
      });
  }, []);

  const handleAction = async (demandeId: string, action: "accepter" | "refuser") => {
    setActionLoading(true);
    setError(null);
    try {
      await apiClient.post(`/v1/mentor/revision-code/demandes/${demandeId}/${action}`, action === "refuser" ? { raison: "Refusé par le mentor." } : {});
      setDemandes((prev) => prev.map(d => d.id === demandeId ? { ...d, statut: action === "accepter" ? "en_cours" : "refusee" } : d));
      if (selected && selected.id === demandeId) setSelected({ ...selected, statut: action === "accepter" ? "en_cours" : "refusee" });
    } catch (e: any) {
      setError(e.message || "Erreur lors de l'action.");
    }
    setActionLoading(false);
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Gestion des demandes</h1>
          <p className="text-muted-foreground">Toutes les demandes reçues et actions disponibles</p>
        </div>
        <Button variant="outline" size="sm" onClick={() => { setShowHistory(true); fetchHistory(); }}>
          <History className="mr-2 h-4 w-4" /> Historique
        </Button>
      </div>
      {/* Statistiques */}
      <div className="grid gap-3 grid-cols-2 md:grid-cols-4 xl:grid-cols-5">
        <Card className="border-l-4 border-l-blue-500 py-3">
          <CardContent className="flex items-center gap-3 p-0 px-4">
            <div className="p-2 rounded-full bg-blue-500/10"><FileText className="h-4 w-4 text-blue-500" /></div>
            <div>
              <p className="text-2xl font-bold">{demandes.length}</p>
              <p className="text-xs text-muted-foreground">Total</p>
            </div>
          </CardContent>
        </Card>
        <Card className="border-l-4 border-l-green-500 py-3">
          <CardContent className="flex items-center gap-3 p-0 px-4">
            <div className="p-2 rounded-full bg-green-500/10"><UserCheck className="h-4 w-4 text-green-500" /></div>
            <div>
              <p className="text-2xl font-bold">{demandes.filter(d => d.statut === "en_attente").length}</p>
              <p className="text-xs text-muted-foreground">En attente</p>
            </div>
          </CardContent>
        </Card>
        <Card className="border-l-4 border-l-purple-500 py-3">
          <CardContent className="flex items-center gap-3 p-0 px-4">
            <div className="p-2 rounded-full bg-purple-500/10"><FileEdit className="h-4 w-4 text-purple-500" /></div>
            <div>
              <p className="text-2xl font-bold">{demandes.filter(d => d.statut === "en_cours").length}</p>
              <p className="text-xs text-muted-foreground">En cours</p>
            </div>
          </CardContent>
        </Card>
        <Card className="border-l-4 border-l-emerald-500 py-3">
          <CardContent className="flex items-center gap-3 p-0 px-4">
            <div className="p-2 rounded-full bg-emerald-500/10"><UserX className="h-4 w-4 text-emerald-500" /></div>
            <div>
              <p className="text-2xl font-bold">{demandes.filter(d => d.statut === "refusee").length}</p>
              <p className="text-xs text-muted-foreground">Refusées</p>
            </div>
          </CardContent>
        </Card>
        <Card className="border-l-4 border-l-orange-500 py-3">
          <CardContent className="flex items-center gap-3 p-0 px-4">
            <div className="p-2 rounded-full bg-orange-500/10"><FileText className="h-4 w-4 text-orange-500" /></div>
            <div>
              <p className="text-2xl font-bold">{demandes.filter(d => d.statut === "terminee").length}</p>
              <p className="text-xs text-muted-foreground">Terminées</p>
            </div>
          </CardContent>
        </Card>
      </div>
      <Separator className="my-6" />
      {/* Filtres */}
      <div className="w-full bg-muted/60 rounded-xl shadow-sm px-4 py-3 mb-4 flex flex-wrap gap-4 items-center justify-between">
        <div className="flex flex-col gap-1 min-w-[140px]">
          <Label htmlFor="statut" className="text-xs font-medium text-muted-foreground mb-1 pl-1">Statut</Label>
          <div className="flex items-center gap-2">
            <Select value={statutFilter} onValueChange={v => { setStatutFilter(v); setPage(1); }}>
              <SelectTrigger className="min-w-[110px] bg-white/80 border border-border rounded-md shadow-xs" id="statut">
                <SelectValue placeholder="Tous" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">Tous</SelectItem>
                <SelectItem value="en_attente">En attente</SelectItem>
                <SelectItem value="en_cours">En cours</SelectItem>
                <SelectItem value="refusee">Refusée</SelectItem>
                <SelectItem value="terminee">Terminée</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>
        <div className="flex flex-col gap-1 min-w-[140px]">
          <Label htmlFor="urgence" className="text-xs font-medium text-muted-foreground mb-1 pl-1">Urgence</Label>
          <div className="flex items-center gap-2">
            <Select value={urgenceFilter} onValueChange={v => { setUrgenceFilter(v); setPage(1); }}>
              <SelectTrigger className="min-w-[110px] bg-white/80 border border-border rounded-md shadow-xs" id="urgence">
                <SelectValue placeholder="Toutes" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">Toutes</SelectItem>
                <SelectItem value="critique">Critique</SelectItem>
                <SelectItem value="haute">Haute</SelectItem>
                <SelectItem value="normale">Normale</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>
        <div className="flex flex-col gap-1 flex-1 min-w-[180px]">
          <Label htmlFor="search" className="text-xs font-medium text-muted-foreground mb-1 pl-1">Recherche</Label>
          <div className="flex items-center gap-2">
            <Input id="search" className="bg-white/80 border border-border rounded-md shadow-xs" placeholder="Titre ou étudiant..." value={search} onChange={e => { setSearch(e.target.value); setPage(1); }} />
          </div>
        </div>
      </div>
      {isLoading ? (
        <div className="flex items-center justify-center min-h-100">
          <Loader2 className="h-8 w-8 animate-spin" />
        </div>
      ) : error ? (
        <div className="text-red-600 py-8 text-center">{error}</div>
      ) : (
        <div className="overflow-x-auto">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Étudiant</TableHead>
                <TableHead>Titre</TableHead>
                <TableHead>Statut</TableHead>
                <TableHead>Urgence</TableHead>
                <TableHead>Projet</TableHead>
                <TableHead>Tâche</TableHead>
                <TableHead>Date</TableHead>
                <TableHead>Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {demandesPage.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={9} className="text-center text-muted-foreground py-8">Aucune demande reçue.</TableCell>
                </TableRow>
              ) : (
                demandesPage.map((demande) => (
                  <TableRow key={demande.id}>
                    <TableCell>
                      <div className="flex items-center gap-2">
                        <Avatar className="h-7 w-7">
                          <AvatarImage src={demande.etudiant?.profil?.avatar || undefined} />
                          <AvatarFallback>{demande.etudiant?.prenom?.charAt(0)}{demande.etudiant?.nom?.charAt(0)}</AvatarFallback>
                        </Avatar>
                        <span className="truncate text-sm">{demande.etudiant?.prenom} {demande.etudiant?.nom}</span>
                      </div>
                    </TableCell>
                    <TableCell className="truncate max-w-45">{demande.titre}</TableCell>
                    <TableCell>
                      <Badge variant={demande.statut === "en_attente" ? "outline" : demande.statut === "en_cours" ? "secondary" : demande.statut === "terminee" ? "default" : "destructive"}>{demande.statut}</Badge>
                    </TableCell>
                    <TableCell>
                      <TooltipProvider>
                        <Tooltip>
                          <TooltipTrigger asChild>
                            <span><Badge variant={demande.urgence === "critique" ? "destructive" : demande.urgence === "haute" ? "default" : "outline"}>{demande.urgence}</Badge></span>
                          </TooltipTrigger>
                          <TooltipContent>Priorité de la demande</TooltipContent>
                        </Tooltip>
                      </TooltipProvider>
                    </TableCell>
                    <TableCell>{demande.projet?.titre || "-"}</TableCell>
                    <TableCell>{demande.tache?.titre || "-"}</TableCell>
                    <TableCell>{demande.created_at ? new Date(demande.created_at).toLocaleString("fr-FR") : "-"}</TableCell>
                    <TableCell>
                      <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                          <Button variant="ghost" size="icon" className="h-8 w-8"><MoreHorizontal className="h-4 w-4" /></Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" className="w-48">
                          <DropdownMenuLabel>Actions</DropdownMenuLabel>
                          <DropdownMenuSeparator />
                          {demande.statut === "en_attente" && (
                            <>
                              <DropdownMenuItem onClick={() => handleAction(demande.id, "accepter")}> <UserCheck className="h-4 w-4 mr-2" /> Accepter</DropdownMenuItem>
                              <DropdownMenuItem onClick={() => handleAction(demande.id, "refuser")}> <UserX className="h-4 w-4 mr-2" /> Refuser</DropdownMenuItem>
                            </>
                          )}
                          {demande.statut === "en_cours" && (
                            <DropdownMenuItem onClick={() => setSelected(demande)}> <FileEdit className="h-4 w-4 mr-2" /> Soumettre révision</DropdownMenuItem>
                          )}
                          <DropdownMenuItem onClick={() => setSelected(demande)}> <FileText className="h-4 w-4 mr-2" /> Détail</DropdownMenuItem>
                        </DropdownMenuContent>
                      </DropdownMenu>
                    </TableCell>
                  </TableRow>
                ))
              )}
            </TableBody>
          </Table>
          {/* Pagination */}
          {totalPages > 1 && (
            <div className="flex items-center justify-end gap-2 mt-4">
              <Button variant="outline" size="sm" disabled={page === 1} onClick={() => setPage(page - 1)}>Précédent</Button>
              <span className="text-sm">Page {page} / {totalPages}</span>
              <Button variant="outline" size="sm" disabled={page === totalPages} onClick={() => setPage(page + 1)}>Suivant</Button>
            </div>
          )}
        </div>
      )}
      {/* Détail de la demande sélectionnée */}
      {selected && (
        <div className="fixed inset-0 bg-black/30 z-40 flex items-center justify-center" onClick={() => setSelected(null)}>
          <div className="bg-white rounded-xl shadow-xl max-w-lg w-full p-6 relative z-50" onClick={e => e.stopPropagation()}>
            <h2 className="text-xl font-bold mb-2 flex items-center gap-2">
              <FileText className="h-5 w-5 text-muted-foreground" /> Détail de la demande
            </h2>
            <Separator className="mb-4" />
            <div className="space-y-2">
              <div><span className="font-semibold">Titre :</span> {selected.titre}</div>
              <div><span className="font-semibold">Description :</span> {selected.description}</div>
              <div><span className="font-semibold">Statut :</span> <Badge variant={selected.statut === "en_attente" ? "outline" : selected.statut === "en_cours" ? "secondary" : selected.statut === "terminee" ? "default" : "destructive"}>{selected.statut}</Badge></div>
              <div><span className="font-semibold">Urgence :</span> <Badge variant={selected.urgence === "critique" ? "destructive" : selected.urgence === "haute" ? "default" : "outline"}>{selected.urgence}</Badge></div>
              <div><span className="font-semibold">Étudiant :</span> {selected.etudiant?.prenom} {selected.etudiant?.nom}</div>
              {selected.projet && <div><span className="font-semibold">Projet :</span> {selected.projet.titre}</div>}
              {selected.tache && <div><span className="font-semibold">Tâche :</span> {selected.tache.titre}</div>}
              <div><span className="font-semibold">Créée le :</span> {selected.created_at ? new Date(selected.created_at).toLocaleString("fr-FR") : "-"}</div>
              {/* Affichage des révisions */}
              {selected.revisions && selected.revisions.length > 0 && (
                <div className="mt-4">
                  <h3 className="font-semibold mb-2">Révision(s) :</h3>
                  {selected.revisions.map((rev, idx) => (
                    <div key={rev.id} className="border rounded p-2 mb-2">
                      <div className="text-xs mb-1">Statut : <Badge variant={rev.statut === "termine" ? "default" : rev.statut === "refuse" ? "destructive" : "outline"}>{rev.statut}</Badge></div>
                      {rev.commentaires && <div className="text-xs">Commentaires : {JSON.stringify(rev.commentaires)}</div>}
                      {rev.points_positifs && <div className="text-xs">Points positifs : {rev.points_positifs.join(", ")}</div>}
                      {rev.points_amelioration && <div className="text-xs">À améliorer : {rev.points_amelioration.join(", ")}</div>}
                      {rev.note_generale !== undefined && <div className="text-xs">Note : {rev.note_generale}/5</div>}
                    </div>
                  ))}
                </div>
              )}
            </div>
            <Separator className="my-4" />
            <div className="flex gap-2 justify-end">
              <Button variant="outline" onClick={() => setSelected(null)}>Fermer</Button>
              {selected.statut === "en_attente" && (
                <>
                  <Button disabled={actionLoading} onClick={() => handleAction(selected.id, "accepter")}>Accepter</Button>
                  <Button variant="destructive" disabled={actionLoading} onClick={() => handleAction(selected.id, "refuser")}>Refuser</Button>
                </>
              )}
              {selected.statut === "en_cours" && (
                <Button disabled={actionLoading} onClick={() => handleReviser(selected.id)}>Soumettre révision</Button>
              )}
            </div>
          </div>
        </div>
      )}
      {/* Historique des révisions */}
      {showHistory && (
        <div className="fixed inset-0 bg-black/30 z-40 flex items-center justify-center" onClick={() => setShowHistory(false)}>
          <div className="bg-white rounded-xl shadow-xl max-w-2xl w-full p-6 relative z-50" onClick={e => e.stopPropagation()}>
            <h2 className="text-xl font-bold mb-2 flex items-center gap-2">
              <History className="h-5 w-5 text-muted-foreground" /> Historique des révisions
            </h2>
            <Separator className="mb-4" />
            {historyLoading ? (
              <div className="flex items-center justify-center min-h-50">
                <Loader2 className="h-6 w-6 animate-spin" />
              </div>
            ) : history.length === 0 ? (
              <div className="text-muted-foreground text-center py-8">Aucune révision trouvée.</div>
            ) : (
              <div className="space-y-4">
                {history.map((rev) => (
                  <div key={rev.id} className="border rounded p-3">
                    <div className="flex items-center gap-2 mb-2">
                      <Badge variant={rev.statut === "termine" ? "default" : rev.statut === "refuse" ? "destructive" : "outline"}>{rev.statut}</Badge>
                      {rev.note_generale !== undefined && <span className="text-xs">Note : {rev.note_generale}/5</span>}
                    </div>
                    {rev.commentaires && <div className="text-xs">Commentaires : {JSON.stringify(rev.commentaires)}</div>}
                    {rev.points_positifs && <div className="text-xs">Points positifs : {rev.points_positifs.join(", ")}</div>}
                    {rev.points_amelioration && <div className="text-xs">À améliorer : {rev.points_amelioration.join(", ")}</div>}
                    {rev.termine_a && <div className="text-xs">Terminé le : {new Date(rev.termine_a).toLocaleString("fr-FR")}</div>}
                  </div>
                ))}
              </div>
            )}
            <div className="flex justify-end mt-6">
              <Button variant="outline" onClick={() => setShowHistory(false)}>Fermer</Button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
