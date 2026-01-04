"use client";

import { useState } from "react";
import Link from "next/link";
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
import { Loader2, AlertCircle, Eye, EyeOff } from "lucide-react";
import { useAuth, useRedirectIfAuthenticated, getRoleDashboardPath } from "@/hooks/use-auth";

const registerSchema = z.object({
  nom: z.string().min(2, "Le nom doit contenir au moins 2 caractères"),
  prenom: z.string().min(2, "Le prénom doit contenir au moins 2 caractères"),
  email: z.string().email("Email invalide"),
  mot_de_passe: z.string().min(8, "Le mot de passe doit contenir au moins 8 caractères"),
  mot_de_passe_confirmation: z.string(),
}).refine((data) => data.mot_de_passe === data.mot_de_passe_confirmation, {
  message: "Les mots de passe ne correspondent pas",
  path: ["mot_de_passe_confirmation"],
});

type RegisterFormData = z.infer<typeof registerSchema>;

export default function InscriptionPage() {
  const { register: registerUser, isLoading, error, clearError } = useAuth();
  const [showPassword, setShowPassword] = useState(false);
  
  const { isLoading: checkingAuth } = useRedirectIfAuthenticated();

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<RegisterFormData>({
    resolver: zodResolver(registerSchema),
  });

  const onSubmit = async (data: RegisterFormData) => {
    try {
      clearError();
      const response = await registerUser(data);
      if (response?.utilisateur?.role) {
        window.location.href = getRoleDashboardPath(response.utilisateur.role);
      }
    } catch {
      // Erreur gérée par le store
    }
  };

  if (checkingAuth) {
    return (
      <div className="flex items-center justify-center">
        <Loader2 className="h-8 w-8 animate-spin" />
      </div>
    );
  }

  return (
    <Card>
      <CardHeader className="space-y-1">
        <CardTitle className="text-2xl text-center">Inscription</CardTitle>
        <CardDescription className="text-center">
          Créez votre compte DevBridge
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

          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="prenom">Prénom</Label>
              <Input
                id="prenom"
                placeholder="John"
                {...register("prenom")}
                disabled={isLoading}
              />
              {errors.prenom && (
                <p className="text-sm text-red-500">{errors.prenom.message}</p>
              )}
            </div>
            <div className="space-y-2">
              <Label htmlFor="nom">Nom</Label>
              <Input
                id="nom"
                placeholder="Doe"
                {...register("nom")}
                disabled={isLoading}
              />
              {errors.nom && (
                <p className="text-sm text-red-500">{errors.nom.message}</p>
              )}
            </div>
          </div>

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
            <Label htmlFor="mot_de_passe">Mot de passe</Label>
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
                Inscription...
              </>
            ) : (
              "S'inscrire"
            )}
          </Button>
        </form>
      </CardContent>
      <CardFooter className="flex justify-center">
        <p className="text-sm text-gray-600">
          Déjà un compte ?{" "}
          <Link href="/connexion" className="text-blue-600 hover:underline">
            Se connecter
          </Link>
        </p>
      </CardFooter>
    </Card>
  );
}
