<?php

namespace App\Tests\Security\Voter;

use App\Entity\OrdreFabrication;
use App\Entity\User;
use App\Security\Voter\OrdreFabricationVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
class OrdreFabricationVoterTest extends TestCase
{
    public function testProprietairePeutModifier(): void
    {
        $user = new User();

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
    $autreUtilisateur = new User();

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
    $admin = new User();

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

    $ordre = new OrdreFabrication();
    $ordre->setUser($proprietaire);

    $anonymousUser = new class implements \Symfony\Component\Security\Core\User\UserInterface {
        public function getRoles(): array
        {
            return [];
        }

        public function getUserIdentifier(): string
        {
            return 'anonymous';
        }

        public function eraseCredentials(): void
        {
        }
    };

    $token = new UsernamePasswordToken(
        $anonymousUser,
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