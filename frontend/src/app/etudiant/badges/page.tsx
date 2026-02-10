"use client";
import { useEffect, useState } from "react";
import { apiClient } from "@/lib/api";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Badge } from "@/components/ui/badge";
import { Trophy, Star, Medal } from "lucide-react";

export default function EtudiantBadgesPage() {
  const [badges, setBadges] = useState<any[]>([]);
  const [points, setPoints] = useState<any[]>([]);
  const [classement, setClassement] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    async function fetchData() {
      setLoading(true);
      try {
        const [badgesRes, pointsRes, classementRes] = await Promise.all([
          apiClient.get<any>("/v1/etudiant/recompenses/badges"),
          apiClient.get<any>("/v1/etudiant/recompenses/points"),
          apiClient.get<any>("/v1/etudiant/recompenses/classement"),
        ]);
        setBadges(badgesRes.data?.badges || []);
        setPoints(pointsRes.data?.historique || pointsRes.data?.points || []);
        setClassement(classementRes.data?.classement || []);
      } catch (e: any) {
        setError(e.message || "Erreur lors du chargement");
      } finally {
        setLoading(false);
      }
    }
    fetchData();
  }, []);

  if (loading) return <div className="py-12 text-center">Chargement...</div>;
  if (error) return <div className="py-12 text-center text-red-600">{error}</div>;

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold">Récompenses</h1>

      <Tabs defaultValue="badges">
        <TabsList>
          <TabsTrigger value="badges">Badges</TabsTrigger>
          <TabsTrigger value="points">Historique points</TabsTrigger>
          <TabsTrigger value="classement">Classement</TabsTrigger>
        </TabsList>

        <TabsContent value="badges" className="mt-4">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {badges.length === 0 ? (
              <div className="text-gray-500">Aucun badge obtenu pour le moment.</div>
            ) : (
              badges.map((b: any) => (
                <Card key={b.id} className="relative overflow-hidden">
                  <CardHeader className="flex flex-row items-center gap-3">
                    <div className="flex h-10 w-10 items-center justify-center rounded-full bg-yellow-100">
                      <Trophy className="h-5 w-5 text-yellow-600" />
                    </div>
                    <div>
                      <CardTitle className="text-base">{b.nom}</CardTitle>
                      {b.type && <Badge variant="secondary" className="text-xs mt-1">{b.type}</Badge>}
                    </div>
                  </CardHeader>
                  <CardContent>
                    <p className="text-sm text-gray-600">{b.description}</p>
                    {b.points && <p className="text-xs text-yellow-600 mt-1">+{b.points} points</p>}
                    <p className="text-xs text-gray-400 mt-2">
                      Obtenu le : {b.obtenu_a || b.created_at ? new Date(b.obtenu_a || b.created_at).toLocaleDateString() : "-"}
                    </p>
                  </CardContent>
                </Card>
              ))
            )}
          </div>
        </TabsContent>

        <TabsContent value="points" className="mt-4">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Star className="h-5 w-5 text-yellow-500" />
                Historique des points
              </CardTitle>
            </CardHeader>
            <CardContent>
              {points.length === 0 ? (
                <p className="text-gray-500">Aucun historique de points.</p>
              ) : (
                <div className="space-y-3">
                  {points.map((p: any, idx: number) => (
                    <div key={idx} className="flex items-center justify-between border-b pb-2 last:border-0">
                      <div>
                        <p className="text-sm font-medium">{p.raison || p.description || "Points"}</p>
                        <p className="text-xs text-gray-400">
                          {p.created_at ? new Date(p.created_at).toLocaleString() : ""}
                        </p>
                      </div>
                      <span className={`text-sm font-bold ${p.points > 0 ? "text-green-600" : "text-red-600"}`}>
                        {p.points > 0 ? "+" : ""}{p.points}
                      </span>
                    </div>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="classement" className="mt-4">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Medal className="h-5 w-5 text-blue-500" />
                Classement global
              </CardTitle>
            </CardHeader>
            <CardContent>
              {classement.length === 0 ? (
                <p className="text-gray-500">Aucun classement disponible.</p>
              ) : (
                <div className="space-y-2">
                  {classement.map((c: any, idx: number) => (
                    <div key={idx} className="flex items-center justify-between border-b pb-2 last:border-0">
                      <div className="flex items-center gap-3">
                        <span className={`text-lg font-bold ${idx < 3 ? "text-yellow-500" : "text-gray-500"}`}>
                          #{c.rang || idx + 1}
                        </span>
                        <div>
                          <p className="text-sm font-medium">{c.nom || c.prenom}</p>
                          <p className="text-xs text-gray-400">Niveau {c.niveau || "-"}</p>
                        </div>
                      </div>
                      <span className="text-sm font-bold text-blue-600">{c.points} pts</span>
                    </div>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
}
