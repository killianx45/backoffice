<?php

namespace App\Controller\Api;

use App\Entity\CsvDataset;
use App\Entity\CsvVariable;
use App\Repository\CsvDatasetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/api/datasets')]
class DatasetApiController extends AbstractController
{
    public function __construct(
        private TranslatorInterface $translator
    ) {}
    #[Route('', name: 'app_api_datasets_list', methods: ['GET'])]
    #[IsGranted('ROLE_AUTEUR')]
    public function list(CsvDatasetRepository $repository): JsonResponse
    {
        $datasets = $repository->findConfigured();

        return $this->json([
            'datasets' => array_map(fn(CsvDataset $d) => [
                'id' => $d->getId(),
                'filename' => $d->getFilename(),
                'originalName' => $d->getOriginalName(),
                'url' => $d->getPublicUrl(),
                'uploadedBy' => $d->getUploadedBy()->getPrenom() . ' ' . $d->getUploadedBy()->getNom(),
                'uploadedAt' => $d->getUploadedAt()->format('Y-m-d H:i:s'),
                'fileSize' => $d->getFileSize(),
                'rowCount' => $d->getRowCount(),
                'variableCount' => $d->getVariables()->count(),
                'isConfigured' => $d->isConfigured()
            ], $datasets)
        ]);
    }

    #[Route('/all', name: 'app_api_datasets_all', methods: ['GET'])]
    #[IsGranted('ROLE_FOURNISSEUR')]
    public function listAll(CsvDatasetRepository $repository): JsonResponse
    {
        $user = $this->getUser();
        $datasets = $repository->findByUser($user);

        return $this->json([
            'datasets' => array_map(fn(CsvDataset $d) => [
                'id' => $d->getId(),
                'filename' => $d->getFilename(),
                'originalName' => $d->getOriginalName(),
                'url' => $d->getPublicUrl(),
                'uploadedAt' => $d->getUploadedAt()->format('Y-m-d H:i:s'),
                'fileSize' => $d->getFileSize(),
                'rowCount' => $d->getRowCount(),
                'variableCount' => $d->getVariables()->count(),
                'isConfigured' => $d->isConfigured()
            ], $datasets)
        ]);
    }

    #[Route('/{id}', name: 'app_api_datasets_show', methods: ['GET'])]
    #[IsGranted('ROLE_AUTEUR')]
    public function show(CsvDataset $dataset): JsonResponse
    {
        return $this->json([
            'dataset' => [
                'id' => $dataset->getId(),
                'filename' => $dataset->getFilename(),
                'originalName' => $dataset->getOriginalName(),
                'url' => $dataset->getPublicUrl(),
                'uploadedBy' => $dataset->getUploadedBy()->getPrenom() . ' ' . $dataset->getUploadedBy()->getNom(),
                'uploadedAt' => $dataset->getUploadedAt()->format('Y-m-d H:i:s'),
                'fileSize' => $dataset->getFileSize(),
                'rowCount' => $dataset->getRowCount(),
                'isConfigured' => $dataset->isConfigured(),
                'variables' => array_map(fn(CsvVariable $v) => [
                    'id' => $v->getId(),
                    'name' => $v->getName(),
                    'type' => $v->getType(),
                    'columnIndex' => $v->getColumnIndex(),
                    'sampleValues' => $v->getSampleValues()
                ], $dataset->getVariables()->toArray())
            ]
        ]);
    }

    #[Route('/{id}/variables/{variableId}', name: 'app_api_datasets_update_variable', methods: ['PATCH'])]
    #[IsGranted('ROLE_FOURNISSEUR')]
    public function updateVariable(
        CsvDataset $dataset,
        int $variableId,
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        $variable = $dataset->getVariables()->filter(fn(CsvVariable $v) => $v->getId() === $variableId)->first();

        if (!$variable) {
            return $this->json(['error' => $this->translator->trans('api.dataset.error.variable_not_found')], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['type'])) {
            if (!in_array($data['type'], [CsvVariable::TYPE_CATEGORICAL, CsvVariable::TYPE_NUMERIC])) {
                return $this->json(['error' => $this->translator->trans('api.dataset.error.invalid_type')], Response::HTTP_BAD_REQUEST);
            }
            $variable->setType($data['type']);
        }

        $em->flush();

        return $this->json([
            'success' => true,
            'variable' => [
                'id' => $variable->getId(),
                'name' => $variable->getName(),
                'type' => $variable->getType(),
                'columnIndex' => $variable->getColumnIndex(),
                'sampleValues' => $variable->getSampleValues()
            ]
        ]);
    }

    #[Route('/{id}/configure', name: 'app_api_datasets_configure', methods: ['POST'])]
    #[IsGranted('ROLE_FOURNISSEUR')]
    public function configure(CsvDataset $dataset, EntityManagerInterface $em): JsonResponse
    {
        $dataset->setIsConfigured(true);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => $this->translator->trans('api.dataset.success.configured')
        ]);
    }

    #[Route('/{id}', name: 'app_api_datasets_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_FOURNISSEUR')]
    public function delete(CsvDataset $dataset, EntityManagerInterface $em): JsonResponse
    {
        if ($dataset->getUploadedBy() !== $this->getUser()) {
            return $this->json(['error' => $this->translator->trans('api.dataset.error.permission_denied')], Response::HTTP_FORBIDDEN);
        }

        $em->remove($dataset);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => $this->translator->trans('api.dataset.success.deleted')
        ]);
    }
}
