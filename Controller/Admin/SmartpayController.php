<?php

/*
 * This file is part of Smartpay
 *
 * Copyright(c) Smartpay Solutions PTE. LTD. All Rights Reserved.
 *
 * https://homepage.smartpay.ninja/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\Smartpay\Controller\Admin;


use Eccube\Controller\AbstractController;
use Eccube\Util\CacheUtil;
use Eccube\Util\StringUtil;
use Plugin\Smartpay\Form\Type\Admin\Smartpay\ConfigType;
use Plugin\Smartpay\Form\Type\Admin\Smartpay\APIKeys;
use Plugin\Smartpay\Form\Type\Admin\Smartpay\CallbackURLs;
use Plugin\Smartpay\Repository\ConfigRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ConfigController
 * @package Plugin\Smartpay\Controller\Admin
 *
 * @Route("/%eccube_admin_route%/smartpay")
 */
class SmartpayController extends AbstractController
{
    /**
     * @var ConfigRepository
     */
    private $configRepository;

    public function __construct(ConfigRepository $configRepository)
    {
        $this->configRepository = $configRepository;
    }

    /**
     * @param Request $request
     * @param CacheUtil $cacheUtil
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("/callback_urls", name="smartpay_callback_urls")
     * @Template("@Smartpay/admin/Smartpay/callback_urls.twig")
     */
    public function callbackUrls(Request $request, CacheUtil $cacheUtil)
    {
        $form = $this->createForm(CallbackURLs::class, [
            'success_url' => getenv('SMARTPAY_SUCCESS_URL'),
            'cancel_url' => getenv('SMARTPAY_CANCEL_URL')
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $this->replaceOrAddEnv([
                'SMARTPAY_SUCCESS_URL' => $data['success_url'],
                'SMARTPAY_CANCEL_URL' => $data['cancel_url']
            ]);

            $cacheUtil->clearCache();

            $this->addSuccess('admin.common.save_complete', 'admin');

            return $this->redirectToRoute('smartpay_admin_config');
        }

        return [
            'form' => $form->createView()
        ];
    }

    /**
     * @param Request $request
     * @param CacheUtil $cacheUtil
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("/api_keys", name="smartpay_api_keys")
     * @Template("@Smartpay/admin/Smartpay/api_keys.twig")
     */
    public function apiKeys(Request $request, CacheUtil $cacheUtil)
    {
        $form = $this->createForm(APIKeys::class, [
            'public_key' => getenv('SMARTPAY_PUBLIC_KEY'),
            'secret_key' => getenv('SMARTPAY_SECRET_KEY')
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $this->replaceOrAddEnv([
                'SMARTPAY_PUBLIC_KEY' => $data['public_key'],
                'SMARTPAY_SECRET_KEY' => $data['secret_key']
            ]);

            $cacheUtil->clearCache();

            $this->addSuccess('admin.common.save_complete', 'admin');

            return $this->redirectToRoute('smartpay_admin_config');
        }

        return [
            'form' => $form->createView()
        ];
    }

    /**
     * @param Request $request
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("/config", name="smartpay_admin_config")
     * @Template("@Smartpay/admin/Smartpay/config.twig")
     */
    public function index(Request $request)
    {
        $Config = $this->configRepository->get();
        $form = $this->createForm(ConfigType::class, $Config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $Config = $form->getData();
            $this->entityManager->persist($Config);
            $this->entityManager->flush();

            $this->addSuccess('smartpay.admin.save.success', 'admin');

            return $this->redirectToRoute('smartpay_admin_config');
        }

        return [
            'form' => $form->createView(),
            'public_key' => getenv('SMARTPAY_PUBLIC_KEY'),
            'success_url' => getenv('SMARTPAY_CANCEL_URL'),
            'cancel_url' => getenv('SMARTPAY_SUCCESS_URL'),
        ];
    }

    private function replaceOrAddEnv(array $replacement)
    {
        $envFile = $this->getParameter('kernel.project_dir') . DIRECTORY_SEPARATOR . '.env';
        if (file_exists($envFile)) {
            $env = file_get_contents($envFile);
            $env = StringUtil::replaceOrAddEnv($env, $replacement);
            file_put_contents($envFile, $env);
        }
    }
}
