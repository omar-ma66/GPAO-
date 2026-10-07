<?php

namespace App\Tests\Security\Voter;

use App\Entity\OrdreFabrication;
use App\Entity\User;
use App\Security\Voter\OrdreFabricationVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;

class OrdreFabricationVoterTest extends TestCase
{
    public function testProprietairePeutModifier(): void
    {
        $user = new User();
        $user->setEmail('user@gpao.local');
        $user->setRoles(['ROLE_USER']);

        $ordre = new OrdreFabrication();
        $ordre->setUser($user);

        $token = new UsernamePasswordToken(
            $user,
            'main',
            $user->getRoles()
        );

        $voter = new OrdreFabricationVoter();

        $resultat = $voter->vote(
            $token,
            $ordre,
            [OrdreFabricationVoter::EDIT]
        );

        self::assertSame(1, $resultat);
    }

    public function testUtilisateurQuiNestPasProprietaireNePeutPasModifier(): void
    {
        $proprietaire = new User();
        $proprietaire->setEmail('proprietaire@gpao.local');
        $proprietaire->setRoles(['ROLE_USER']);

        $autreUtilisateur = new User();
        $autreUtilisateur->setEmail('autre@gpao.local');
        $autreUtilisateur->setRoles(['ROLE_USER']);

        $ordre = new OrdreFabrication();
        $ordre->setUser($proprietaire);

        $token = new UsernamePasswordToken(
            $autreUtilisateur,
            'main',
            $autreUtilisateur->getRoles()
        );

        $voter = new OrdreFabricationVoter();

        $resultat = $voter->vote(
            $token,
            $ordre,
            [OrdreFabricationVoter::EDIT]
        );

        self::assertSame(-1, $resultat);
    }

    public function testAdminPeutModifierNimporteQuelOrdre(): void
    {
        $proprietaire = new User();
        $proprietaire->setEmail('proprietaire@gpao.local');
        $proprietaire->setRoles(['ROLE_USER']);

        $admin = new User();
        $admin->setEmail('admin@gpao.local');
        $admin->setRoles(['ROLE_ADMIN']);

        $ordre = new OrdreFabrication();
        $ordre->setUser($proprietaire);

        $token = new UsernamePasswordToken(
            $admin,
            'main',
            $admin->getRoles()
        );

        $voter = new OrdreFabricationVoter();

        $resultat = $voter->vote(
            $token,
            $ordre,
            [OrdreFabricationVoter::EDIT]
        );

        self::assertSame(1, $resultat);
    }

    public function testUtilisateurNonConnecteNePeutPasModifier(): void
    {
        $proprietaire = new User();
        $proprietaire->setEmail('proprietaire@gpao.local');
        $proprietaire->setRoles(['ROLE_USER']);

        $ordre = new OrdreFabrication();
        $ordre->setUser($proprietaire);

        $utilisateurAnonyme = new class implements UserInterface {
            public function getRoles(): array
            {
                return [];
            }

            public function eraseCredentials(): void
            {
            }

            public function getUserIdentifier(): string
            {
                return '';
            }
        };

        $token = new UsernamePasswordToken(
            $utilisateurAnonyme,
            'main',
            []
        );

        $voter = new OrdreFabricationVoter();

        $resultat = $voter->vote(
            $token,
            $ordre,
            [OrdreFabricationVoter::EDIT]
        );

        self::assertSame(-1, $resultat);
    }

    public function testVoterSabstientPourUnAutreAttribut(): void
    {
        $user = new User();
        $user->setEmail('user@gpao.local');
        $user->setRoles(['ROLE_USER']);

        $ordre = new OrdreFabrication();
        $ordre->setUser($user);

        $token = new UsernamePasswordToken(
            $user,
            'main',
            $user->getRoles()
        );

        $voter = new OrdreFabricationVoter();

        $resultat = $voter->vote(
            $token,
            $ordre,
            ['DELETE']
        );

        self::assertSame(0, $resultat);
    }
}