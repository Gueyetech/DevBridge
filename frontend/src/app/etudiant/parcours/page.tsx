"use client";
import { useEffect, useState, useCallback } from "react";
import { apiClient } from "@/lib/api";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Progress } from "@/components/ui/progress";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { BookOpen, Play, CheckCircle, ArrowLeft, ChevronRight } from "lucide-react";

export default function EtudiantParcoursPage() {
  const [parcours, setParcours] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  // Detail view
  const [selectedParcours, setSelectedParcours] = useState<any>(null);
  const [detailLoading, setDetailLoading] = useState(false);
  const [progression, setProgression] = useState<any>(null);
  const [prochainContenu, setProchainContenu] = useState<any>(null);

  const fetchParcours = useCallback(async () => {
    setLoading(true);
    try {
      const res = await apiClient.get<any>("/v1/etudiant/parcours");
      setParcours(res.data?.parcours || []);
    } catch (e: any) {
      setError(e.message || "Erreur lors du chargement");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchParcours();
  }, [fetchParcours]);

  async function handleViewDetail(id: number) {
    setDetailLoading(true);
    try {
      const [detailRes, progressionRes] = await Promise.all([
        apiClient.get<any>(`/v1/etudiant/parcours/${id}`),
        apiClient.get<any>(`/v1/etudiant/parcours/${id}/progression`).catch(() => null),
      ]);
      setSelectedParcours(detailRes.data?.parcours || detailRes.data);
      setProgression(progressionRes?.data || null);

      // Try to get next content
      try {
        const nextRes = await apiClient.get<any>(`/v1/etudiant/parcours/${id}/prochain-contenu`);
        setProchainContenu(nextRes.data);
      } catch {
        setProchainContenu(null);
      }
    } catch (e: any) {
      alert(e.message || "Erreur lors du chargement du détail");
    } finally {
      setDetailLoading(false);
    }
  }

  async function handleInscrire(id: number) {
    try {
      await apiClient.post(`/v1/etudiant/parcours/${id}/inscrire`);
      alert("Inscription réussie !");
      fetchParcours();
      if (selectedParcours?.id === id) handleViewDetail(id);
    } catch (e: any) {
      alert(e.message || "Erreur lors de l'inscription");
    }
  }

  async function handleTerminerLecon(parcoursId: number, leconId: number) {
    try {
      await apiClient.post(`/v1/etudiant/parcours/${parcoursId}/lecons/${leconId}/terminer`);
      handleViewDetail(parcoursId);
    } catch (e: any) {
      alert(e.message || "Erreur lors de la validation");
    }
  }

  if (loading) return <div className="py-12 text-center">Chargement...</div>;
  if (error) return <div className="py-12 text-center text-red-600">{error}</div>;

  // Detail view
  if (selectedParcours) {
    const modules = selectedParcours.modules || [];
    const progressionPct = progression?.pourcentage || progression?.progression || 0;
    return (
      <div className="space-y-6">
        <Button variant="ghost" onClick={() => { setSelectedParcours(null); setProgression(null); setProchainContenu(null); }}>
          <ArrowLeft className="h-4 w-4 mr-2" /> Retour aux parcours
        </Button>

        <div className="flex items-start justify-between">
          <div>
            <h1 className="text-2xl font-bold">{selectedParcours.titre}</h1>
            <p className="text-gray-600 mt-1">{selectedParcours.description}</p>
            <div className="flex gap-2 mt-2">
              {selectedParcours.technologie && (
                <Badge variant="secondary">{selectedParcours.technologie}</Badge>
              )}
              {selectedParcours.difficulte && (
                <Badge variant="outline">{selectedParcours.difficulte}</Badge>
              )}
            </div>
          </div>
          {!selectedParcours.est_inscrit && !selectedParcours.inscription && (
            <Button onClick={() => handleInscrire(selectedParcours.id)}>
              <Play className="h-4 w-4 mr-2" /> S&apos;inscrire
            </Button>
          )}
        </div>

        {/* Progression */}
        {progression && (
          <Card>
            <CardHeader>
              <CardTitle className="text-base">Progression</CardTitle>
            </CardHeader>
            <CardContent>
              <Progress value={progressionPct} className="h-3" />
              <p className="text-sm text-gray-500 mt-2">{progressionPct}% complété</p>
              {progression.lecons_terminees !== undefined && (
                <p className="text-xs text-gray-400">
                  {progression.lecons_terminees} / {progression.lecons_totales || "?"} leçons terminées
                </p>
              )}
            </CardContent>
          </Card>
        )}

        {/* Prochain contenu */}
        {prochainContenu && (
          <Card className="border-blue-200 bg-blue-50">
            <CardHeader>
              <CardTitle className="text-base flex items-center gap-2">
                <ChevronRight className="h-4 w-4 text-blue-600" />
                Prochain contenu
              </CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-sm font-medium">{prochainContenu.titre || prochainContenu.lecon?.titre || "Leçon suivante"}</p>
              {prochainContenu.type && <Badge variant="secondary" className="mt-1">{prochainContenu.type}</Badge>}
            </CardContent>
          </Card>
        )}

        {/* Modules et leçons */}
        {modules.length > 0 && (
          <div className="space-y-4">
            <h2 className="text-lg font-semibold">Modules</h2>
            {modules.map((mod: any) => (
              <Card key={mod.id}>
                <CardHeader>
                  <CardTitle className="text-base">{mod.titre}</CardTitle>
                </CardHeader>
                <CardContent>
                  {mod.description && <p className="text-sm text-gray-600 mb-3">{mod.description}</p>}
                  {(mod.lecons || []).length > 0 && (
                    <div className="space-y-2">
                      {mod.lecons.map((lecon: any) => (
                        <div key={lecon.id} className="flex items-center justify-between p-2 rounded border">
                          <div className="flex items-center gap-2">
                            {lecon.terminee ? (
                              <CheckCircle className="h-4 w-4 text-green-500" />
                            ) : (
                              <BookOpen className="h-4 w-4 text-gray-400" />
                            )}
                            <span className={`text-sm ${lecon.terminee ? "line-through text-gray-400" : ""}`}>
                              {lecon.titre}
                            </span>
                          </div>
                          {!lecon.terminee && (
                            <Button
                              size="sm"
                              variant="outline"
                              onClick={() => handleTerminerLecon(selectedParcours.id, lecon.id)}
                            >
                              <CheckCircle className="h-3 w-3 mr-1" /> Terminer
                            </Button>
                          )}
                        </div>
                      ))}
                    </div>
                  )}
                </CardContent>
              </Card>
            ))}
          </div>
        )}
      </div>
    );
  }

  // List view
  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold">Mes parcours</h1>
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {parcours.length === 0 ? (
          <div className="text-gray-500 col-span-full">Aucun parcours trouvé.</div>
        ) : (
          parcours.map((p: any) => (
            <Card key={p.id} className="cursor-pointer hover:shadow-md transition-shadow" onClick={() => handleViewDetail(p.id)}>
              <CardHeader>
                <CardTitle className="text-base">{p.titre}</CardTitle>
              </CardHeader>
              <CardContent>
                <p className="text-sm text-gray-600 line-clamp-2">{p.description}</p>
                <div className="flex gap-2 mt-2">
                  {p.technologie && <Badge variant="secondary">{p.technologie}</Badge>}
                  {p.difficulte && <Badge variant="outline">{p.difficulte}</Badge>}
                </div>
                {p.progression !== undefined && p.progression !== null && (
                  <div className="mt-3">
                    <Progress value={p.progression} className="h-2" />
                    <p className="text-xs text-gray-400 mt-1">{p.progression}%</p>
                  </div>
                )}
                {p.est_inscrit && (
                  <Badge className="mt-2 bg-green-100 text-green-800">Inscrit</Badge>
                )}
              </CardContent>
            </Card>
          ))
        )}
      </div>
    </div>
  );
}
