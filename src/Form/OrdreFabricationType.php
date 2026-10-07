<?php

namespace App\Form;

use App\Entity\MatierePremiere;
use App\Entity\OrdreFabrication;
use App\Entity\Produit;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class OrdreFabricationType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder
            ->add('quantite', IntegerType::class, [
                'label' => 'Quantité',
                'constraints' => [
                    new Assert\Positive(),
                ],
            ])

            ->add('produit', EntityType::class, [
                'label' => 'Produit',
                'class' => Produit::class,
                'choice_label' => 'nom',
            ])

            ->add('matieresPremieres', EntityType::class, [
                'label' => 'Matières premières utilisées',
                'class' => MatierePremiere::class,
                'choice_label' => function (MatierePremiere $matiere): string {
                    return $matiere->getNom()
                        . ' (' . $matiere->getReference() . ')';
                },
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'by_reference' => false,

                'choice_attr' => function (
                    ?MatierePremiere $matiere
                ): array {
                    if (!$matiere) {
                        return [];
                    }

                    $productIds = [];

                    foreach ($matiere->getProduits() as $produit) {
                        if ($produit->getId() !== null) {
                            $productIds[] = (string) $produit->getId();
                        }
                    }

                    return [
                        'data-product-ids' => implode(',', $productIds),
                    ];
                },
            ]);
    }

    public function configureOptions(
        OptionsResolver $resolver
    ): void {
        $resolver->setDefaults([
            'data_class' => OrdreFabrication::class,
        ]);
    }
}
