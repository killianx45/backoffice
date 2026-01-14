<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
    ) {}

    public function load(ObjectManager $manager): void
    {
        $roles = [
            "ROLE_ADMIN" => ["nom" => "Admin", "prenom" => "Super"],
            "ROLE_EDITEUR" => ["nom" => "Editeur", "prenom" => "Edit"],
            "ROLE_AUTEUR" => ["nom" => "Auteur", "prenom" => "Écrit"],
            "ROLE_DESIGNEUR" => ["nom" => "Designeur", "prenom" => "Design"],
            "ROLE_FOURNISSEUR" => [
                "nom" => "Fournisseur",
                "prenom" => "Fournis",
            ],
            "ROLE_ABONNE" => ["nom" => "Abonne", "prenom" => "Abonné"],
        ];

        foreach ($roles as $role => $userData) {
            $user = new User();

            // Génère l'email basé sur le rôle (ex: admin@example.com)
            $roleSlug = strtolower(str_replace("ROLE_", "", $role));
            $user->setEmail($roleSlug . "@example.com");

            // Génère le mot de passe: SAE501_ + 3 premières lettres du rôle + @
            $rolePrefix = substr(str_replace("ROLE_", "", $role), 0, 3);
            $plainPassword = "SAE501_" . $rolePrefix . "@";
            $hashedPassword = $this->passwordHasher->hashPassword(
                $user,
                $plainPassword,
            );
            $user->setPassword($hashedPassword);

            $user->setRoles([$role]);
            $user->setNom($userData["nom"]);
            $user->setPrenom($userData["prenom"]);
            $user->setIsVerified(true);

            $manager->persist($user);
        }

        $manager->flush();
    }
}
