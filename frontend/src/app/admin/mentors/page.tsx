"use client";
import { useEffect, useState, useCallback } from "react";
import { apiClient } from "@/lib/api";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from "@/components/ui/table";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Loader2, Users, CheckCircle, XCircle, Eye } from "lucide-react";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from "@/components/ui/dialog";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";

export default function AdminMentorsPage() {
  const [mentors, setMentors] = useState<any[]>([]);
  const [demandes, setDemandes] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [actionLoading, setActionLoading] = useState(false);
  const [tab, setTab] = useState("mentors");

  const fetchAll = useCallback(async () => {
    setLoading(true);
    try {
      const [mRes, dRes] = await Promise.all([
        apiClient.get<any>("/v1/admin/mentors"),
        apiClient.get<any>("/v1/admin/mentors/demandes").catch(() => ({ data: {} })),
      ]);
      setMentors(mRes.data?.mentors || mRes.data || []);
      setDemandes(dRes.data?.demandes || dRes.data || []);
    } catch (e: any) { setError(e.message); }
    setLoading(false);
  }, []);

  useEffect(() => { fetchAll(); }, [fetchAll]);

  const handleValider = async (id: number) => {
    setActionLoading(true);
    try { await apiClient.post(`/v1/admin/mentors/${id}/valider`); fetchAll(); } catch (e: any) { setError(e.message); }
    setActionLoading(false);
  };

  const handleRevoquer = async (id: number) => {
    if (!confirm("Révoquer ce mentor ?")) return;
    setActionLoading(true);
    try { await apiClient.post(`/v1/admin/mentors/${id}/revoquer`); fetchAll(); } catch (e: any) { setError(e.message); }
    setActionLoading(false);
  };

  if (loading) return <div className="py-12 text-center"><Loader2 className="h-8 w-8 animate-spin mx-auto" /></div>;

  return (
    <div className="space-y-6">
      <div><h1 className="text-2xl font-bold flex items-center gap-2"><Users className="h-6 w-6" /> Gestion des mentors</h1><p className="text-muted-foreground">{mentors.length} mentors, {demandes.length} demandes</p></div>
      {error && <div className="text-red-600 text-sm bg-red-50 p-3 rounded-lg">{error}</div>}

      <div className="grid grid-cols-2 gap-4">
        <Card className="p-4 text-center"><p className="text-2xl font-bold">{mentors.length}</p><p className="text-xs text-muted-foreground">Mentors actifs</p></Card>
        <Card className="p-4 text-center"><p className="text-2xl font-bold">{demandes.length}</p><p className="text-xs text-muted-foreground">Demandes en attente</p></Card>
      </div>

      <Tabs value={tab} onValueChange={setTab}>
        <TabsList><TabsTrigger value="mentors">Mentors ({mentors.length})</TabsTrigger><TabsTrigger value="demandes">Demandes ({demandes.length})</TabsTrigger></TabsList>

        <TabsContent value="mentors" className="mt-4">
          {mentors.length === 0 ? <Card><CardContent className="py-12 text-center text-muted-foreground">Aucun mentor.</CardContent></Card> : (
            <Table>
              <TableHeader><TableRow><TableHead>Mentor</TableHead><TableHead>Email</TableHead><TableHead>Spécialités</TableHead><TableHead>Étudiants</TableHead><TableHead>Statut</TableHead><TableHead>Actions</TableHead></TableRow></TableHeader>
              <TableBody>
                {mentors.map((m: any) => (
                  <TableRow key={m.id}>
                    <TableCell><div className="flex items-center gap-2"><Avatar className="h-7 w-7"><AvatarImage src={m.avatar} /><AvatarFallback className="text-xs">{m.prenom?.[0]}{m.nom?.[0]}</AvatarFallback></Avatar>{m.prenom} {m.nom}</div></TableCell>
                    <TableCell className="text-xs">{m.email}</TableCell>
                    <TableCell><div className="flex flex-wrap gap-1">{(m.specialites || []).slice(0, 3).map((s: string, i: number) => <Badge key={i} variant="outline" className="text-xs">{s}</Badge>)}</div></TableCell>
                    <TableCell>{m.nombre_etudiants || 0}</TableCell>
                    <TableCell><Badge variant={m.valide ? "default" : "secondary"}>{m.valide ? "Validé" : "Non validé"}</Badge></TableCell>
                    <TableCell className="flex gap-1">
                      {!m.valide && <Button size="sm" variant="ghost" onClick={() => handleValider(m.id)} disabled={actionLoading}><CheckCircle className="h-3 w-3 text-green-600" /></Button>}
                      <Button size="sm" variant="ghost" onClick={() => handleRevoquer(m.id)} disabled={actionLoading}><XCircle className="h-3 w-3 text-red-500" /></Button>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </TabsContent>

        <TabsContent value="demandes" className="mt-4">
          {demandes.length === 0 ? <Card><CardContent className="py-12 text-center text-muted-foreground">Aucune demande en attente.</CardContent></Card> : (
            <Table>
              <TableHeader><TableRow><TableHead>Utilisateur</TableHead><TableHead>Email</TableHead><TableHead>Date</TableHead><TableHead>Actions</TableHead></TableRow></TableHeader>
              <TableBody>
                {demandes.map((d: any) => (
                  <TableRow key={d.id}>
                    <TableCell><div className="flex items-center gap-2"><Avatar className="h-7 w-7"><AvatarFallback className="text-xs">{d.prenom?.[0]}{d.nom?.[0]}</AvatarFallback></Avatar>{d.prenom} {d.nom}</div></TableCell>
                    <TableCell className="text-xs">{d.email}</TableCell>
                    <TableCell className="text-xs">{d.created_at ? new Date(d.created_at).toLocaleDateString("fr-FR") : "-"}</TableCell>
                    <TableCell className="flex gap-1">
                      <Button size="sm" onClick={() => handleValider(d.id)} disabled={actionLoading}><CheckCircle className="h-3 w-3 mr-1" /> Valider</Button>
                      <Button size="sm" variant="destructive" onClick={() => handleRevoquer(d.id)} disabled={actionLoading}><XCircle className="h-3 w-3 mr-1" /> Refuser</Button>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </TabsContent>
      </Tabs>
    </div>
  );
}
