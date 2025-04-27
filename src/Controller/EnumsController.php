<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ConfigEntity;
use App\Form\EnumFormType;
use App\Model\EnumeratorManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EnumsController extends AbstractController
{
    public function __construct(private readonly FormFactoryInterface $formFactory)
    {
    }

    #[Route('/admin/dashboard/enums', 'enums_index')]
    public function getEnums(Request $request, EntityManagerInterface $em): Response
    {
        $names = [
            EnumeratorManager::SET_FAQS,
            EnumeratorManager::SET_TALK_CATEGORIES,
            EnumeratorManager::SET_TALK_ROOMS,
            EnumeratorManager::SET_TALK_DURATIONS,
        ];
        $forms = [];
        foreach ($names as $item) {
            $data = $em->getRepository(ConfigEntity::class)->find($item);
            $form = $forms[$item] = $this->createEnumForm($item, $data?->getValue() ?? []);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $data ??= new ConfigEntity($item, []);
                $data->setValue($form->getData()['enums']);
                $em->persist($data);
                $em->flush();

                return $this->redirectToRoute('enums_index');
            }
        }
        return $this->render('admin/dashboard_enums.html.twig', [
            'forms' => array_map(fn(FormInterface $form) => $form->createView(), $forms),
        ]);
    }

    public function createEnumForm(string $name, array $data): FormInterface
    {
        return $this->formFactory->createNamedBuilder($name, data: [
            'enums' => $data,
        ])
            ->add('enums', CollectionType::class, [
                'entry_type' => EnumFormType::class,
                'label_html' => false,
                'allow_add' => true,
                'allow_delete' => true,
            ])
            ->add('save', SubmitType::class)
            ->getForm()
        ;
    }

}
