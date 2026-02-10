"use client";
import { useEffect, useState, useCallback } from "react";
import { apiClient } from "@/lib/api";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from "@/components/ui/table";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from "@/components/ui/dialog";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Loader2, FolderKanban, Trash2, Eye, Settings } from "lucide-react";

export default function AdminProjetsPage() {
  const [projets, setProjets] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [selected, setSelected] = useState<any>(null);
  const [showStatut, setShowStatut] = useState<any>(null);
  const [newStatut, setNewStatut] = useState("en_cours");
  const [actionLoading, setActionLoading] = useState(false);

  const fetchAll = useCallback(async () => {
    setLoading(true);
    try {
      const res = await apiClient.get<any>("/v1/admin/projets");
      setProjets(res.data?.projets || res.data || []);
    } catch (e: any) { setError(e.message); }
    setLoading(false);
  }, []);

  useEffect(() => { fetchAll(); }, [fetchAll]);

  const handleChangeStatut = async () => {
    if (!showStatut) return;
    setActionLoading(true);
    try {
      await apiClient.put(`/v1/admin/projets/${showStatut.id}/statut`, { statut: newStatut });
      setShowStatut(null);
      fetchAll();
    } catch (e: any) { setError(e.message); }
    setActionLoading(false);
  };

  const handleDelete = async (id: number) => {
    if (!confirm("Supprimer ce projet ?")) return;
    try { await apiClient.delete(`/v1/admin/projets/${id}`); fetchAll(); } catch (e: any) { setError(e.message); }
  };

  if (loading) return <div className="py-12 text-center"><Loader2 className="h-8 w-8 animate-spin mx-auto" /></div>;

  return (
    <div className="space-y-6">
      <div><h1 className="text-2xl font-bold flex items-center gap-2"><FolderKanban className="h-6 w-6" /> Gestion des projets</h1><p className="text-muted-foreground">{projets.length} projets</p></div>
      {error && <div className="text-red-600 text-sm bg-red-50 p-3 rounded-lg">{error}</div>}

      {projets.length === 0 ? <Card><CardContent className="py-12 text-center text-muted-foreground">Aucun projet.</CardContent></Card> : (
        <Table>
          <TableHeader><TableRow><TableHead>Titre</TableHead><TableHead>Statut</TableHead><TableHead>Technologie</TableHead><TableHead>Membres</TableHead><TableHead>Créé le</TableHead><TableHead>Actions</TableHead></TableRow></TableHeader>
          <TableBody>
            {projets.map((p: any) => (
              <TableRow key={p.id}>
                <TableCell className="font-semibold">{p.titre}</TableCell>
                <TableCell><Badge variant={p.statut === "termine" ? "secondary" : "outline"}>{p.statut}</Badge></TableCell>
                <TableCell>{p.technologie || "-"}</TableCell>
                <TableCell>{p.nombre_membres || p.membres?.length || 0}</TableCell>
                <TableCell className="text-xs text-muted-foreground">{p.created_at ? new Date(p.created_at).toLocaleDateString("fr-FR") : "-"}</TableCell>
                <TableCell className="flex gap-1">
                  <Button size="sm" variant="ghost" onClick={() => setSelected(p)}><Eye className="h-3 w-3" /></Button>
                  <Button size="sm" variant="ghost" onClick={() => { setShowStatut(p); setNewStatut(p.statut || "en_cours"); }}><Settings className="h-3 w-3" /></Button>
                  <Button size="sm" variant="ghost" className="text-red-500" onClick={() => handleDelete(p.id)}><Trash2 className="h-3 w-3" /></Button>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      )}

      {/* Detail Dialog */}
      {selected && (
        <Dialog open onOpenChange={() => setSelected(null)}>
          <DialogContent><DialogHeader><DialogTitle>{selected.titre}</DialogTitle><DialogDescription>{selected.description}</DialogDescription></DialogHeader>
            <div className="space-y-2 text-sm">
              <div><span className="font-semibold">Statut :</span> {selected.statut}</div>
              <div><span className="font-semibold">Technologie :</span> {selected.technologie || "-"}</div>
              <div><span className="font-semibold">Difficulté :</span> {selected.difficulte || "-"}</div>
              <div><span className="font-semibold">Membres :</span> {selected.nombre_membres || selected.membres?.length || 0}</div>
            </div>
            <DialogFooter><Button variant="outline" onClick={() => setSelected(null)}>Fermer</Button></DialogFooter>
          </DialogContent>
        </Dialog>
      )}

      {/* Change Status Dialog */}
      <Dialog open={!!showStatut} onOpenChange={() => setShowStatut(null)}>
        <DialogContent><DialogHeader><DialogTitle>Changer le statut</DialogTitle><DialogDescription>{showStatut?.titre}</DialogDescription></DialogHeader>
          <Select value={newStatut} onValueChange={setNewStatut}><SelectTrigger><SelectValue /></SelectTrigger><SelectContent><SelectItem value="en_attente">En attente</SelectItem><SelectItem value="en_cours">En cours</SelectItem><SelectItem value="termine">Terminé</SelectItem><SelectItem value="archive">Archivé</SelectItem></SelectContent></Select>
          <DialogFooter><Button variant="outline" onClick={() => setShowStatut(null)}>Annuler</Button><Button onClick={handleChangeStatut} disabled={actionLoading}>{actionLoading ? <Loader2 className="h-4 w-4 animate-spin" /> : "Enregistrer"}</Button></DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
