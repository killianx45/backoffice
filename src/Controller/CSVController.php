<?php

namespace App\Controller;

use App\Entity\CsvDataset;
use App\Entity\CsvVariable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_FOURNISSEUR')]
class CSVController extends AbstractController
{
    private const MAX_FILE_SIZE = 2 * 1024 * 1024;
    private const ALLOWED_MIME_TYPES = ['text/csv', 'text/plain', 'application/csv'];

    public function __construct(
        private readonly TranslatorInterface $translator
    ) {
    }

    #[Route('/cms/csv', name: 'app_csv_index')]
    public function index(): Response
    {
        return $this->render('csv/index.html.twig', [
            'controller_name' => 'CSVController',
        ]);
    }

    #[Route('/api/csv/dataset/{id}/configure', name: 'app_csv_configure', methods: ['GET'])]
    public function configure(CsvDataset $dataset): Response
    {
        if ($dataset->getUploadedBy() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('csv/configure.html.twig', [
            'dataset' => $dataset,
        ]);
    }

    #[Route('/api/csv/upload', name: 'app_csv_upload', methods: ['POST'])]
    public function upload(Request $request, SluggerInterface $slugger, EntityManagerInterface $em): JsonResponse
    {
        $file = $request->files->get('file');

        if (!$file) {
            return $this->json(['error' => $this->translator->trans('csv.error.no_file')], Response::HTTP_BAD_REQUEST);
        }

        if ($file->getSize() > self::MAX_FILE_SIZE) {
            return $this->json(['error' => $this->translator->trans('csv.error.file_too_large')], Response::HTTP_BAD_REQUEST);
        }

        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES)) {
            return $this->json(['error' => $this->translator->trans('csv.error.invalid_type')], Response::HTTP_BAD_REQUEST);
        }

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.csv';

        try {
            $csvDirectory = $this->getParameter('kernel.project_dir') . '/public/media/csv';

            if (!is_dir($csvDirectory)) {
                mkdir($csvDirectory, 0755, true);
            }

            $file->move($csvDirectory, $newFilename);
            $filePath = $csvDirectory . '/' . $newFilename;

            $analysisResult = $this->analyzeCSV($filePath);

            $dataset = new CsvDataset();
            $dataset->setFilename($newFilename);
            $dataset->setOriginalName($file->getClientOriginalName());
            $dataset->setUploadedBy($this->getUser());
            $dataset->setFileSize(filesize($filePath));
            $dataset->setRowCount($analysisResult['rowCount']);

            foreach ($analysisResult['variables'] as $varData) {
                $variable = new CsvVariable();
                $variable->setName($varData['name']);
                $variable->setType($varData['type']);
                $variable->setColumnIndex($varData['columnIndex']);
                $variable->setSampleValues($varData['sampleValues']);
                $dataset->addVariable($variable);
            }

            $em->persist($dataset);
            $em->flush();

            return $this->json([
                'success' => true,
                'datasetId' => $dataset->getId(),
                'filename' => $newFilename,
                'url' => '/media/csv/' . $newFilename,
                'variables' => array_map(fn($v) => [
                    'id' => $v->getId(),
                    'name' => $v->getName(),
                    'type' => $v->getType(),
                    'columnIndex' => $v->getColumnIndex(),
                    'sampleValues' => $v->getSampleValues()
                ], $dataset->getVariables()->toArray()),
                'message' => $this->translator->trans('csv.success.uploaded')
            ]);
        } catch (FileException $e) {
            return $this->json(['error' => $this->translator->trans('csv.error.upload_failed')], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function analyzeCSV(string $filePath): array
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new \RuntimeException('Cannot open CSV file');
        }

        $delimiter = $this->detectDelimiter($filePath);
        $headers = fgetcsv($handle, 0, $delimiter);

        if (!$headers) {
            fclose($handle);
            throw new \RuntimeException('CSV file has no headers');
        }

        $dataRows = [];
        $rowCount = 0;
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false && $rowCount < 100) {
            $dataRows[] = $row;
            $rowCount++;
        }

        $totalRows = $rowCount;
        while (fgetcsv($handle, 0, $delimiter) !== false) {
            $totalRows++;
        }
        fclose($handle);

        $variables = [];
        foreach ($headers as $index => $header) {
            $columnValues = array_column($dataRows, $index);
            $isNumeric = $this->isNumericColumn($columnValues);

            $sampleValues = array_slice(array_filter($columnValues, fn($v) => $v !== ''), 0, 5);

            $variables[] = [
                'name' => trim($header),
                'type' => $isNumeric ? CsvVariable::TYPE_NUMERIC : CsvVariable::TYPE_CATEGORICAL,
                'columnIndex' => $index,
                'sampleValues' => $sampleValues
            ];
        }

        return [
            'rowCount' => $totalRows,
            'variables' => $variables
        ];
    }

    private function detectDelimiter(string $filePath): string
    {
        $handle = fopen($filePath, 'r');
        $firstLine = fgets($handle);
        fclose($handle);

        $delimiters = [',', ';', "\t", '|'];
        $maxCount = 0;
        $detectedDelimiter = ',';

        foreach ($delimiters as $delimiter) {
            $count = substr_count($firstLine, $delimiter);
            if ($count > $maxCount) {
                $maxCount = $count;
                $detectedDelimiter = $delimiter;
            }
        }

        return $detectedDelimiter;
    }

    private function isNumericColumn(array $values): bool
    {
        $nonEmptyValues = array_filter($values, fn($v) => $v !== '' && $v !== null);

        if (empty($nonEmptyValues)) {
            return false;
        }

        foreach ($nonEmptyValues as $value) {
            if (!is_numeric($value)) {
                return false;
            }
        }

        return true;
    }

    #[Route('/api/csv/delete/{filename}', name: 'app_csv_delete', methods: ['DELETE'])]
    public function delete(string $filename): JsonResponse
    {
        $csvDirectory = $this->getParameter('kernel.project_dir') . '/public/media/csv';
        $filePath = $csvDirectory . '/' . $filename;

        if (!file_exists($filePath)) {
            return $this->json(['error' => $this->translator->trans('csv.error.not_found')], Response::HTTP_NOT_FOUND);
        }

        if (!str_ends_with($filename, '.csv')) {
            return $this->json(['error' => $this->translator->trans('csv.error.invalid_file_type')], Response::HTTP_BAD_REQUEST);
        }

        try {
            unlink($filePath);
            return $this->json(['success' => true, 'message' => $this->translator->trans('csv.success.deleted')]);
        } catch (\Exception $e) {
            return $this->json(['error' => $this->translator->trans('csv.error.delete_failed')], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/csv/preview/{filename}', name: 'app_csv_preview', methods: ['GET'])]
    public function preview(string $filename): JsonResponse
    {
        $csvDirectory = $this->getParameter('kernel.project_dir') . '/public/media/csv';
        $filePath = $csvDirectory . '/' . $filename;

        if (!file_exists($filePath)) {
            return $this->json(['error' => $this->translator->trans('csv.error.not_found')], Response::HTTP_NOT_FOUND);
        }

        try {
            $content = file_get_contents($filePath);
            $lines = array_slice(explode("\n", $content), 0, 10);

            return $this->json([
                'success' => true,
                'filename' => $filename,
                'preview' => implode("\n", $lines),
                'lineCount' => count(explode("\n", $content))
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $this->translator->trans('csv.error.read_failed')], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
