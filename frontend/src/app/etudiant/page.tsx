"use client";


import { useEffect, useState } from "react";
import { apiClient } from "@/lib/api";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { BookOpen, Trophy, Clock, Target } from "lucide-react";

function EtudiantDashboard() {
  const [stats, setStats] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [parcoursActifs, setParcoursActifs] = useState<any[]>([]);
  const [activiteRecente, setActiviteRecente] = useState<any[]>([]);

  useEffect(() => {
    async function fetchData() {
      setLoading(true);
      try {
        // Récupère le tableau de bord global (nouvelle structure)
        const res = await apiClient.get<any>("/v1/etudiant/tableau-de-bord");
        const data = res.data || {};
        setStats(data.statistiques || {});
        setParcoursActifs(data.parcours_actifs || []);
        setActiviteRecente(data.activite_recente || []);
        setParcoursRecommandes(data.parcours_recommandes || []);
      } catch (e: any) {
        setError(e.message || "Erreur lors du chargement");
      } finally {
        setLoading(false);
      }
    }
    fetchData();
  }, []);
  const [parcoursRecommandes, setParcoursRecommandes] = useState<any[]>([]);

  if (loading) return <div className="py-12 text-center">Chargement...</div>;
  if (error) return <div className="py-12 text-center text-red-600">{error}</div>;

  return (
    <div className="space-y-6">
      <h1 className="text-3xl font-bold">Tableau de bord</h1>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Parcours en cours</CardTitle>
            <BookOpen className="h-4 w-4 text-gray-500" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.parcours_termines ?? '-'}</div>
            <p className="text-xs text-gray-500">{parcoursActifs.length} parcours actifs</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Badges obtenus</CardTitle>
            <Trophy className="h-4 w-4 text-gray-500" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.badges_obtenus ?? '-'}</div>
            <p className="text-xs text-gray-500">Total</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Temps d'étude</CardTitle>
            <Clock className="h-4 w-4 text-gray-500" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.temps_apprentissage_total ?? '-'}</div>
            <p className="text-xs text-gray-500">Total cumulé</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Niveau</CardTitle>
            <Target className="h-4 w-4 text-gray-500" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.niveau ?? '-'}</div>
            <p className="text-xs text-gray-500">Points : {stats?.points_totaux ?? '-'}</p>
          </CardContent>
        </Card>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <Card>
          <CardHeader>
            <CardTitle>Continuer l'apprentissage</CardTitle>
          </CardHeader>
          <CardContent>
            {parcoursActifs.length === 0 ? (
              <div>
                <p className="text-gray-500 mb-2">Aucun parcours actif.</p>
                {parcoursRecommandes.length > 0 && (
                  <div>
                    <div className="font-semibold mb-1">Parcours recommandés :</div>
                    <ul className="space-y-2">
                      {parcoursRecommandes.map((p: any) => (
                        <li key={p.id} className="border-b pb-2">
                          <div className="font-semibold">{p.titre}</div>
                          <div className="text-xs text-gray-500">{p.description}</div>
                          <div className="text-xs text-gray-400">Technologie : {p.technologie} | Difficulté : {p.difficulte}</div>
                        </li>
                      ))}
                    </ul>
                  </div>
                )}
              </div>
            ) : (
              <ul className="space-y-2">
                {parcoursActifs.map((p: any) => (
                  <li key={p.id} className="border-b pb-2">
                    <div className="font-semibold">{p.titre}</div>
                    <div className="text-xs text-gray-500">Progression : {p.progression ?? '-'}%</div>
                  </li>
                ))}
              </ul>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Activité récente</CardTitle>
          </CardHeader>
          <CardContent>
            {(!activiteRecente || activiteRecente.length === 0) ? (
              <p className="text-gray-500">Aucune activité récente.</p>
            ) : (
              <ul className="space-y-2">
                {activiteRecente.map((a: any, idx: number) => (
                  <li key={idx} className="border-b pb-2">
                    <div className="font-semibold">{a.titre || a.type || 'Activité'}</div>
                    <div className="text-xs text-gray-500">{a.date ? new Date(a.date).toLocaleString() : ''}</div>
                  </li>
                ))}
              </ul>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
 



export default EtudiantDashboard;
