"use client";
import { useEffect, useState } from "react";
import { apiClient } from "@/lib/api";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { ClipboardCheck, Play, Trophy, BarChart3 } from "lucide-react";

export default function EtudiantQuizPage() {
  const [tentatives, setTentatives] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  // Quiz flow states
  const [quizIdInput, setQuizIdInput] = useState("");
  const [activeQuiz, setActiveQuiz] = useState<any>(null);
  const [answers, setAnswers] = useState<Record<number, any>>({});
  const [submitting, setSubmitting] = useState(false);
  const [resultats, setResultats] = useState<any>(null);
  const [classement, setClassement] = useState<any[]>([]);
  const [classementQuizId, setClassementQuizId] = useState<string | null>(null);

  async function fetchTentatives() {
    setLoading(true);
    try {
      const res = await apiClient.get<any>("/v1/etudiant/quiz/tentatives");
      setTentatives(res.data?.tentatives || []);
    } catch (e: any) {
      setError(e.message || "Erreur lors du chargement");
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    fetchTentatives();
  }, []);

  async function handleStartQuiz(quizId: string | number) {
    try {
      const res = await apiClient.get<any>(`/v1/etudiant/quiz/${quizId}/commencer`);
      setActiveQuiz(res.data);
      setAnswers({});
      setResultats(null);
    } catch (e: any) {
      alert(e.message || "Impossible de démarrer le quiz");
    }
  }

  async function handleSubmitQuiz() {
    if (!activeQuiz?.tentative?.id) return;
    setSubmitting(true);
    try {
      const quizId = activeQuiz.quiz?.id || activeQuiz.quiz_id;
      const tentativeId = activeQuiz.tentative?.id;
      const res = await apiClient.post<any>(
        `/v1/etudiant/quiz/${quizId}/tentatives/${tentativeId}/soumettre`,
        { reponses: answers }
      );
      setResultats(res.data);
      setActiveQuiz(null);
      fetchTentatives();
    } catch (e: any) {
      alert(e.message || "Erreur lors de la soumission");
    } finally {
      setSubmitting(false);
    }
  }

  async function handleViewResults(quizId: number, tentativeId: number) {
    try {
      const res = await apiClient.get<any>(
        `/v1/etudiant/quiz/${quizId}/tentatives/${tentativeId}/resultats`
      );
      setResultats(res.data);
    } catch (e: any) {
      alert(e.message || "Impossible de charger les résultats");
    }
  }

  async function handleViewClassement(quizId: number) {
    try {
      const res = await apiClient.get<any>(`/v1/etudiant/quiz/${quizId}/classement`);
      setClassement(res.data?.classement || []);
      setClassementQuizId(String(quizId));
    } catch (e: any) {
      alert(e.message || "Impossible de charger le classement");
    }
  }

  if (loading) return <div className="py-12 text-center">Chargement...</div>;
  if (error) return <div className="py-12 text-center text-red-600">{error}</div>;

  // Active quiz - show questions
  if (activeQuiz) {
    const questions = activeQuiz.questions || activeQuiz.quiz?.questions || [];
    return (
      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <h1 className="text-2xl font-bold">
            {activeQuiz.quiz?.titre || "Quiz en cours"}
          </h1>
          <Button variant="outline" onClick={() => setActiveQuiz(null)}>Annuler</Button>
        </div>
        <div className="space-y-4">
          {questions.map((q: any, idx: number) => (
            <Card key={q.id || idx}>
              <CardHeader>
                <CardTitle className="text-base">
                  Question {idx + 1}: {q.question || q.enonce}
                </CardTitle>
              </CardHeader>
              <CardContent>
                {q.options || q.choix ? (
                  <div className="space-y-2">
                    {(q.options || q.choix || []).map((opt: any, optIdx: number) => {
                      const optValue = typeof opt === "string" ? opt : opt.texte || opt.label;
                      const optId = typeof opt === "string" ? optIdx : opt.id || optIdx;
                      return (
                        <label key={optIdx} className="flex items-center gap-2 cursor-pointer p-2 rounded hover:bg-gray-50">
                          <input
                            type="radio"
                            name={`q-${q.id || idx}`}
                            checked={answers[q.id || idx] === optId}
                            onChange={() => setAnswers({ ...answers, [q.id || idx]: optId })}
                          />
                          <span className="text-sm">{optValue}</span>
                        </label>
                      );
                    })}
                  </div>
                ) : (
                  <Input
                    placeholder="Votre réponse..."
                    value={answers[q.id || idx] || ""}
                    onChange={(e) => setAnswers({ ...answers, [q.id || idx]: e.target.value })}
                  />
                )}
              </CardContent>
            </Card>
          ))}
        </div>
        <Button onClick={handleSubmitQuiz} disabled={submitting} className="w-full">
          {submitting ? "Soumission..." : "Soumettre les réponses"}
        </Button>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold">Quiz</h1>
      </div>

      {/* Démarrer un quiz */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2 text-base">
            <Play className="h-4 w-4" /> Démarrer un quiz
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="flex gap-2">
            <Input
              placeholder="ID du quiz..."
              value={quizIdInput}
              onChange={(e) => setQuizIdInput(e.target.value)}
              className="max-w-xs"
            />
            <Button onClick={() => handleStartQuiz(quizIdInput)} disabled={!quizIdInput}>
              Commencer
            </Button>
          </div>
        </CardContent>
      </Card>

      {/* Résultats dialog */}
      {resultats && (
        <Dialog open={!!resultats} onOpenChange={() => setResultats(null)}>
          <DialogContent className="max-w-lg">
            <DialogHeader>
              <DialogTitle>Résultats du quiz</DialogTitle>
            </DialogHeader>
            <div className="space-y-3">
              <div className="flex justify-between">
                <span>Score :</span>
                <span className="font-bold text-blue-600">
                  {resultats.score ?? resultats.tentative?.score ?? "-"} / {resultats.score_maximum ?? resultats.quiz?.score_maximum ?? "-"}
                </span>
              </div>
              {resultats.pourcentage !== undefined && (
                <div className="flex justify-between">
                  <span>Pourcentage :</span>
                  <span className="font-bold">{resultats.pourcentage}%</span>
                </div>
              )}
              {resultats.reussi !== undefined && (
                <div className="flex justify-between">
                  <span>Statut :</span>
                  <Badge variant={resultats.reussi ? "default" : "destructive"}>
                    {resultats.reussi ? "Réussi" : "Échoué"}
                  </Badge>
                </div>
              )}
            </div>
          </DialogContent>
        </Dialog>
      )}

      {/* Classement dialog */}
      {classementQuizId && (
        <Dialog open={!!classementQuizId} onOpenChange={() => setClassementQuizId(null)}>
          <DialogContent className="max-w-lg">
            <DialogHeader>
              <DialogTitle>Classement du quiz</DialogTitle>
            </DialogHeader>
            <div className="space-y-2">
              {classement.length === 0 ? (
                <p className="text-gray-500">Aucun classement disponible.</p>
              ) : (
                classement.map((c: any, idx: number) => (
                  <div key={idx} className="flex items-center justify-between border-b pb-2 last:border-0">
                    <div className="flex items-center gap-2">
                      <span className={`font-bold ${idx < 3 ? "text-yellow-500" : "text-gray-500"}`}>
                        #{c.rang || idx + 1}
                      </span>
                      <span className="text-sm">{c.nom || c.prenom || "Étudiant"}</span>
                    </div>
                    <span className="text-sm font-bold text-blue-600">{c.score} pts</span>
                  </div>
                ))
              )}
            </div>
          </DialogContent>
        </Dialog>
      )}

      {/* Historique des tentatives */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2 text-base">
            <ClipboardCheck className="h-4 w-4" /> Mes tentatives
          </CardTitle>
        </CardHeader>
        <CardContent>
          {tentatives.length === 0 ? (
            <p className="text-gray-500">Aucune tentative de quiz pour le moment.</p>
          ) : (
            <div className="space-y-3">
              {tentatives.map((t: any) => (
                <div key={t.id} className="flex items-center justify-between border-b pb-3 last:border-0">
                  <div>
                    <p className="text-sm font-medium">{t.quiz?.titre || `Quiz #${t.quiz_id}`}</p>
                    <p className="text-xs text-gray-500">
                      Score: {t.score ?? "-"} / {t.score_maximum ?? t.quiz?.score_maximum ?? "-"}
                    </p>
                    <p className="text-xs text-gray-400">
                      {t.created_at ? new Date(t.created_at).toLocaleString() : ""}
                    </p>
                  </div>
                  <div className="flex gap-2">
                    <Button
                      size="sm"
                      variant="outline"
                      onClick={() => handleViewResults(t.quiz_id, t.id)}
                    >
                      <BarChart3 className="h-3 w-3 mr-1" /> Résultats
                    </Button>
                    <Button
                      size="sm"
                      variant="outline"
                      onClick={() => handleViewClassement(t.quiz_id)}
                    >
                      <Trophy className="h-3 w-3 mr-1" /> Classement
                    </Button>
                  </div>
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
