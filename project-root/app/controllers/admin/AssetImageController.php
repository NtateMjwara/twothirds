<?php
namespace app\controllers\admin;

use app\core\AdminController;
use app\models\Company;
use app\models\Asset;
use app\models\AssetImage;
use app\services\AssetImageService;

class AssetImageController extends AdminController
{
    public function index(string $reference): void
    {
        $this->requireAdmin();
        $company = $this->findCompanyOr404($reference);
        if (!$company) return;

        $this->renderGallery($company);
    }

    public function store(string $reference): void
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        $company = $this->findCompanyOr404($reference);
        if (!$company) return;

        $assets = Asset::where('company_id', $company['id']);
        $assetId = isset($assets[0]) ? (int) $assets[0]['id'] : null;
        $captions = $_POST['captions'] ?? [];

        // The input is multiple, so PHP hands back parallel arrays rather than a
        // list of files. Walking the index is the only way to pair each
        // temp file with its own name, size and error.
        $files = $_FILES['images'] ?? null;
        if (!$files || !is_array($files['name'])) {
            $this->renderGallery($company, 'Choose at least one image to upload.');
            return;
        }

        $uploaded = 0;
        $errors = [];

        foreach ($files['name'] as $i => $name) {
            if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            try {
                AssetImageService::upload(
                    (int) $company['id'],
                    [
                        'name'     => $name,
                        'type'     => $files['type'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                        'error'    => $files['error'][$i],
                        'size'     => $files['size'][$i],
                    ],
                    $assetId,
                    trim((string) ($captions[$i] ?? ''))
                );
                $uploaded++;
            } catch (\InvalidArgumentException $e) {
                // One bad file in a batch shouldn't discard the good ones —
                // report it and keep going.
                $errors[] = $name . ': ' . $e->getMessage();
            }
        }

        if ($uploaded > 0) {
            // If nothing has ever been chosen as the cover, promote whatever now
            // sorts first, so the company page has an explicit hero rather than
            // relying on the fallback ordering.
            $first = AssetImage::primaryForCompany((int) $company['id']);
            if ($first && (int) $first['is_primary'] === 0) {
                AssetImage::makePrimary((int) $company['id'], (int) $first['id']);
            }
        }

        if ($errors) {
            $this->renderGallery($company, implode(' | ', $errors));
            return;
        }

        $this->redirect('/admin/companies/' . $company['reference'] . '/images');
    }

    public function makePrimary(string $reference, string $id): void
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        $company = $this->findCompanyOr404($reference);
        if (!$company) return;

        $image = AssetImage::find((int) $id);
        // Check the image belongs to this company: the id arrives in the URL and
        // an admin of one company shouldn't be able to reassign another's cover.
        if ($image && (int) $image['company_id'] === (int) $company['id']) {
            AssetImage::makePrimary((int) $company['id'], (int) $image['id']);
        }

        $this->redirect('/admin/companies/' . $company['reference'] . '/images');
    }

    public function destroy(string $reference, string $id): void
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        $company = $this->findCompanyOr404($reference);
        if (!$company) return;

        $image = AssetImage::find((int) $id);
        if ($image && (int) $image['company_id'] === (int) $company['id']) {
            $wasPrimary = (int) $image['is_primary'] === 1;
            AssetImageService::delete($image);

            // Deleting the cover leaves the company without one; promote whatever
            // is now first so the page doesn't quietly lose its hero.
            if ($wasPrimary) {
                $next = AssetImage::primaryForCompany((int) $company['id']);
                if ($next) {
                    AssetImage::makePrimary((int) $company['id'], (int) $next['id']);
                }
            }
        }

        $this->redirect('/admin/companies/' . $company['reference'] . '/images');
    }

    private function renderGallery(array $company, ?string $error = null): void
    {
        $this->render('admin/images/index', [
            'company' => $company,
            'images'  => AssetImage::forCompany((int) $company['id']),
            'error'   => $error,
        ]);
    }

    private function findCompanyOr404(string $reference): ?array
    {
        $company = Company::findByReference($reference);
        if (!$company) {
            http_response_code(404);
            $this->render('errors/404');
            return null;
        }
        return $company;
    }
}
