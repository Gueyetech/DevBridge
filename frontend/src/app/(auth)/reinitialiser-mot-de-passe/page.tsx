"use client";

import { useState } from "react";
import Link from "next/link";
import { useSearchParams } from "next/navigation";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Loader2, AlertCircle, CheckCircle, Eye, EyeOff } from "lucide-react";
import { apiClient } from "@/lib/api";

const resetPasswordSchema = z.object({
  email: z.string().email("Email invalide"),
  mot_de_passe: z.string().min(8, "Le mot de passe doit contenir au moins 8 caractères"),
  mot_de_passe_confirmation: z.string(),
}).refine((data) => data.mot_de_passe === data.mot_de_passe_confirmation, {
  message: "Les mots de passe ne correspondent pas",
  path: ["mot_de_passe_confirmation"],
});

type ResetPasswordFormData = z.infer<typeof resetPasswordSchema>;

export default function ReinitialiserMotDePassePage() {
  const searchParams = useSearchParams();
  const token = searchParams.get("token");
  const emailParam = searchParams.get("email");
  
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState(false);
  const [showPassword, setShowPassword] = useState(false);

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<ResetPasswordFormData>({
    resolver: zodResolver(resetPasswordSchema),
    defaultValues: {
      email: emailParam || "",
    },
  });

  const onSubmit = async (data: ResetPasswordFormData) => {
    if (!token) {
      setError("Token de réinitialisation manquant");
      return;
    }

    setIsLoading(true);
    setError(null);
    try {
      await apiClient.getCsrfCookie();
      await apiClient.post("/auth/reinitialiser-mot-de-passe", {
        ...data,
        token,
      });
      setSuccess(true);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Une erreur est survenue");
    } finally {
      setIsLoading(false);
    }
  };

  if (!token) {
    return (
      <Card>
        <CardHeader>
          <CardTitle className="text-2xl text-center">Lien invalide</CardTitle>
        </CardHeader>
        <CardContent>
          <Alert variant="destructive">
            <AlertCircle className="h-4 w-4" />
            <AlertDescription>
              Le lien de réinitialisation est invalide ou a expiré.
            </AlertDescription>
          </Alert>
        </CardContent>
        <CardFooter className="flex justify-center">
          <Link href="/mot-de-passe-oublie" className="text-blue-600 hover:underline">
            Demander un nouveau lien
          </Link>
        </CardFooter>
      </Card>
    );
  }

  if (success) {
    return (
      <Card>
        <CardHeader className="space-y-1">
          <CardTitle className="text-2xl text-center">Mot de passe réinitialisé</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <Alert>
            <CheckCircle className="h-4 w-4 text-green-600" />
            <AlertDescription>
              Votre mot de passe a été réinitialisé avec succès. Vous pouvez
              maintenant vous connecter.
            </AlertDescription>
          </Alert>
        </CardContent>
        <CardFooter className="flex justify-center">
          <Link href="/connexion">
            <Button>Se connecter</Button>
          </Link>
        </CardFooter>
      </Card>
    );
  }

  return (
    <Card>
      <CardHeader className="space-y-1">
        <CardTitle className="text-2xl text-center">Nouveau mot de passe</CardTitle>
        <CardDescription className="text-center">
          Créez un nouveau mot de passe pour votre compte
        </CardDescription>
      </CardHeader>
      <CardContent>
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
          {error && (
            <Alert variant="destructive">
              <AlertCircle className="h-4 w-4" />
              <AlertDescription>{error}</AlertDescription>
            </Alert>
          )}

          <div className="space-y-2">
            <Label htmlFor="email">Email</Label>
            <Input
              id="email"
              type="email"
              placeholder="votre@email.com"
              {...register("email")}
              disabled={isLoading}
            />
            {errors.email && (
              <p className="text-sm text-red-500">{errors.email.message}</p>
            )}
          </div>

          <div className="space-y-2">
            <Label htmlFor="mot_de_passe">Nouveau mot de passe</Label>
            <div className="relative">
              <Input
                id="mot_de_passe"
                type={showPassword ? "text" : "password"}
                placeholder="••••••••"
                {...register("mot_de_passe")}
                disabled={isLoading}
              />
              <Button
                type="button"
                variant="ghost"
                size="sm"
                className="absolute right-0 top-0 h-full px-3 py-2 hover:bg-transparent"
                onClick={() => setShowPassword(!showPassword)}
              >
                {showPassword ? (
                  <EyeOff className="h-4 w-4" />
                ) : (
                  <Eye className="h-4 w-4" />
                )}
              </Button>
            </div>
            {errors.mot_de_passe && (
              <p className="text-sm text-red-500">{errors.mot_de_passe.message}</p>
            )}
          </div>

          <div className="space-y-2">
            <Label htmlFor="mot_de_passe_confirmation">Confirmer le mot de passe</Label>
            <Input
              id="mot_de_passe_confirmation"
              type="password"
              placeholder="••••••••"
              {...register("mot_de_passe_confirmation")}
              disabled={isLoading}
            />
            {errors.mot_de_passe_confirmation && (
              <p className="text-sm text-red-500">{errors.mot_de_passe_confirmation.message}</p>
            )}
          </div>

          <Button type="submit" className="w-full" disabled={isLoading}>
            {isLoading ? (
              <>
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                Réinitialisation...
              </>
            ) : (
              "Réinitialiser le mot de passe"
            )}
          </Button>
        </form>
      </CardContent>
    </Card>
  );
}
