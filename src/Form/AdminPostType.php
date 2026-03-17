<?php

namespace App\Form;

use App\Entity\Post;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

class AdminPostType extends PostType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder->add('status', ChoiceType::class, [
            'label' => 'Statut',
            'choices' => [
                'Brouillon' => Post::STATUS_DRAFT,
                'En attente' => Post::STATUS_PENDING,
                'Publié' => Post::STATUS_PUBLISHED,
                'Rejeté' => Post::STATUS_REJECTED,
            ],
        ]);
    }
}
