<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 */

namespace flipbox\saml\core\controllers\messages;


use craft\web\Controller;
use craft\web\Request;
use flipbox\saml\core\helpers\SerializeHelper;
use flipbox\saml\core\records\ProviderInterface;
use flipbox\saml\core\traits\EnsureSamlPlugin;
use LightSaml\Model\Assertion\Issuer;
use LightSaml\Model\Protocol\AbstractRequest;
use LightSaml\Model\Protocol\LogoutResponse;
use LightSaml\Model\Protocol\LogoutRequest;
use LightSaml\Model\Protocol\SamlMessage;
use LightSaml\Model\Protocol\StatusResponse;
use yii\web\HttpException;

abstract class AbstractLogoutController extends Controller
{
    use EnsureSamlPlugin;

    protected $allowAnonymous = [
        'actionIndex',
    ];

    public $enableCsrfValidation = false;

    /**
     * @return ProviderInterface
     */
    abstract protected function getRemoteProvider(): ProviderInterface;

    /**
     * @param AbstractRequest $samlMessage
     * @param ProviderInterface $provider
     */
    abstract protected function send(SamlMessage $samlMessage, ProviderInterface $provider);

    /**
     * @param Request $request
     * @return StatusResponse
     */
    abstract protected function receive(Request $request): StatusResponse;

    /**
     * @param \yii\base\Action $action
     * @return bool
     */
    public function beforeAction($action)
    {
        if ($action->actionMethod === 'actionIndex') {
            return true;
        }
        return parent::beforeAction($action);
    }


    /**
     * @return \yii\web\Response
     * @throws HttpException
     * @throws \flipbox\saml\core\exceptions\InvalidIssuer
     */
    public function actionIndex()
    {
        $message = $this->receive(\Craft::$app->request);

        $isRequest = $message instanceof LogoutRequest;
        $isResponse = $message instanceof LogoutResponse;

        if ($isResponse && $this->getSamlPlugin()->getSession()->getRequestId() !== $message->getInResponseTo()) {
            throw new HttpException(400, "Invalid request");
        }

        /**
         * I guess we shouldn't be here. Just follow the normal logout.
         */
        if (\Craft::$app->getUser()->isGuest) {
            return $this->redirect(
                \Craft::$app->config->general->logoutPath
            );
        }

        /** @var Issuer $issuer */
        $issuer = $message->getIssuer();

        /** @var ProviderInterface $provider */
        $provider = $this->getSamlPlugin()->getHttpPost()->getProviderByIssuer($issuer);

        if ($isRequest) {
            /** @var AbstractRequest $message */
            $response = $this->getSamlPlugin()->getLogoutResponse()->create($message);

            /**
             * Add the request id to the the response.
             */
            $response->setInResponseTo($message->getID());

            $this->send($response, $provider);
            exit;
        }

        return $this->redirect(
            \Craft::$app->config->general->logoutPath
        );
    }


    public function actionRequest()
    {

        /** @var ProviderInterface $provider */
        $provider = $this->getRemoteProvider();

        $logoutRequest = $this->getSamlPlugin()->getLogoutRequest()->create($provider);

        /**
         * Save id to session so we can validate the response.
         */
        $this->getSamlPlugin()->getSession()->setRequestId($logoutRequest->getID());

        $this->send($logoutRequest, $provider);
        exit;
    }

}