<?php

namespace CustomForm\MailAttachment\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;

class Submit extends \Magento\Framework\App\Action\Action
{
    /**
     * Recipient email config path
     */
    const XML_PATH_EMAIL_RECIPIENT = 'trans_email/ident_custom2/email';
    const XML_PATH_EMAIL_SENDER = 'trans_email/ident_custom1/email';
    const XML_PATH_EMAIL_SENDER_NAME = 'trans_email/ident_custom1/name';
    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $_transportBuilder;

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $inlineTranslation;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var \Magento\Framework\Escaper
     */
    protected $_escaper;

    protected $fileUploaderFactory;

    protected $fileSystem;

    protected $_resultRedirectFactory;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\App\Action\Context                           $context,
        \CustomForm\MailAttachment\Model\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface              $inlineTranslation,
        \Magento\Framework\App\Config\ScopeConfigInterface              $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface                      $storeManager,
        \Magento\MediaStorage\Model\File\UploaderFactory                $fileUploaderFactory,
        Filesystem                                                      $fileSystem,
        \Magento\Framework\Escaper                                      $escaper
    )
    {
        parent::__construct($context);
        $this->_transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->fileUploaderFactory = $fileUploaderFactory;
        $this->fileSystem = $fileSystem;
        $this->_escaper = $escaper;
        $this->_resultRedirectFactory = $context->getResultRedirectFactory();
    }

    /**
     * Post user question
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     * @throws \Exception
     */
    public function execute()
    {
        $resultRedirect = $this->_resultRedirectFactory->create();
        $post = $this->getRequest()->getPostValue();
        if (!$post) {
            $this->_redirect('*/*/');
            return;
        }
        try {
            $postObject = new \Magento\Framework\DataObject();
            $postObject->setData($post);
            $error = false;
            $filesData = $this->getRequest()->getFiles('document');
            if ($filesData['name']) {
                $uploader = $this->fileUploaderFactory->create(['fileId' => 'document']);
                # 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
                # "Restrict allowed file extensions in CustomForm\MailAttachment\Controller\Index\Submit to prevent unrestricted file uploads to pub/media/CustomImage":
                # https://github.com/smokeday-com/m/issues/7
                $uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png', 'pdf', 'doc', 'docx']);
                $uploader->setAllowRenameFiles(true);
                $uploader->setFilesDispersion(true);
                $uploader->setAllowCreateFolders(true);
                $path = $this->fileSystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath('CustomImage');
                $result = $uploader->save($path);
                $upload_document = 'CustomImage' . $uploader->getUploadedFilename();
                $filePath = $result['path'] . $result['file'];
                $fileName = $result['name'];
                $fileType = $result['type'];
            } else {
                $upload_document = '';
                $filePath = '';
                $fileName = '';
                $fileType = '';
            }
            $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            $sender = ['name' => $this->scopeConfig->getValue(self::XML_PATH_EMAIL_SENDER_NAME, $storeScope), 'email' => $this->scopeConfig->getValue(self::XML_PATH_EMAIL_SENDER, $storeScope)];
            $transport = $this->_transportBuilder->setTemplateIdentifier('send_email_custom_template') // this code we have mentioned in the email_templates.xml
            ->setTemplateOptions(
                [
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND, // this is using frontend area to get the template file
                    'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID,
                ]
            )
                ->setTemplateVars(['data' => $postObject])
                ->setFrom($sender)
                ->addTo($this->scopeConfig->getValue(self::XML_PATH_EMAIL_RECIPIENT, $storeScope))
                ->setReplyTo($this->_escaper->escapeHtml($post['email']), $this->_escaper->escapeHtml($post['firstname'] . ' ' . $post['lastname']))
                //->addAttachment(file_get_contents($filePath), $fileName,$fileType)
                ->getTransport();
            $transport->sendMessage();
            $this->messageManager->addSuccess(__('Your inquiry was submitted and will be responded to as soon as possible. Thank you for registering with us.'));
            $this->inlineTranslation->resume();
            return $resultRedirect->setPath('*/*/');
        } catch (\Exception $e) {
            $this->inlineTranslation->resume();
            $this->messageManager->addError(__('Unable to submit your request. Please, try again later.' . $e->getMessage()));
            return $resultRedirect->setPath('*/*/');
        }
    }
}
