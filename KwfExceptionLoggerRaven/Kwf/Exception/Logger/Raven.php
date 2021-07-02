<?php
class KwfExceptionLoggerRaven_Kwf_Exception_Logger_Raven extends Kwf_Exception_Logger_Abstract
{
    private $tags = ARRAY();
    private $additionalData = ARRAY();

    public function __construct()
    {
        $this->_fileLogger = new Kwf_Exception_Logger_LogFiles();
    }

    /**
     * @param array $tags
     */
    public function setTags($tags)
    {
        $this->tags = $tags;
    }

    /**
     * @param array $additionalData
     */
    public function setAdditionalData($additionalData)
    {
        $this->additionalData = $additionalData;
    }

    /**
     * @param $key
     * @param $value
     */
    public function addTag($key, $value)
    {
        $this->tags[$key] = $value;
    }

    /**
     * @param $key
     * @param $value
     */
    public function addAdditionalData($key, $value)
    {
        $this->additionalData[$key] = $value;
    }


    public function log(Kwf_Exception_Abstract $exception, $type, $content)
    {
        $this->_fileLogger->log($exception, $type, $content);

        if ($type != 'error') return; //don't log notfound or accessdenied
        if ($exception instanceof Kwf_Exception_JavaScript) return; //don't log client side errors

        $dsn = Kwf_Config::getValue('ravenPhp.dsn');
        if (!$dsn) {
            throw new Kwf_Exception("Can't submit exception using raven: ravenPhp.dsn not configured");
        }
        $options = ['dsn' => $dsn];
        if (Kwf_Config::getValue('http.proxy.host')) {
            $options['http_proxy'] = Kwf_Config::getValue('http.proxy.host');
            if (Kwf_Config::getValue('http.proxy.port')) {
                $options['http_proxy'] .= ':' . Kwf_Config::getValue('http.proxy.port');
            }
        }

        \Sentry\init($options);

        $user = ["id" => "guest"];
        try {
            if ($u = Kwf_Registry::get('userModel')->getAuthedUser()) {
                $user = [
                    'id' => $u->id,
                    'email' => $u->email,
                    'role' => $u->role
                ];
            }
        } catch (Exception $e) {
            $user = null;
        }
        if ($user) {
            \Sentry\configureScope(function (\Sentry\State\Scope $scope) use($user) {
                $scope->setUser($user);
            });
        }

        if (count($this->tags) > 0) {
            \Sentry\configureScope(function (\Sentry\State\Scope $scope) {
                $scope->setTags($this->tags);
            });
        }

        if (count($this->additionalData) > 0) {
            \Sentry\configureScope(function (\Sentry\State\Scope $scope) {
                $scope->setContext('additionalData', $this->additionalData);
            });
        }

        try {
            \Sentry\captureLastError();
        } catch(Exception $e) {
            $to = array();
            foreach (Kwf_Registry::get('config')->developers as $dev) {
                if (isset($dev->sendException) && $dev->sendException) {
                    $to[] = $dev->email;
                }
            }
            if ($to) {
                mail(implode(', ', $to),
                    'Error while trying to submit exception using raven',
                    $e->__toString()."\n\n---------------------------\n\nOriginal Exception:\n\n".$content
                );
            }
        }
    }
}
