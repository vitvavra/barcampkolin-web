<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class EnumFormType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('key', TextType::class, ['label' => 'Klíč'])
            ->add('value', TextType::class, ['label' => 'Hodnota'])
            ->add('remove', SubmitType::class, ['label' => 'Odstranit'])
            ->add('add', ButtonType::class, [
                'label' => 'Přidat další',
                'attr' => [
                    'class' => 'btn btn-primary add_item_link',
                    'data-collection-holder-class' => 'enums',
                ],
            ])
            ->add('update', SubmitType::class, ['label' => 'Uložit'])
        ;
    }

}
