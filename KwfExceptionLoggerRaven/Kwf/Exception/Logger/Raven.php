<?php
class KwfExceptionLoggerRaven_Kwf_Exception_Logger_Raven extends Kwf_Exception_Logger_Abstract
{
    public function __construct()
    {
        $this->_fileLogger = new Kwf_Exception_Logger_LogFiles();
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
        $options = array();
        if (Kwf_Config::getValue('http.proxy.host')) {
            $options['http_proxy'] = Kwf_Config::getValue('http.proxy.host');
            if (Kwf_Config::getValue('http.proxy.port')) {
                $options['http_proxy'] .= ':' . Kwf_Config::getValue('http.proxy.port');
            }
        }
        $client = new Raven_Client($dsn, $options);

        $user = "guest";
        try {
            if ($u = Kwf_Registry::get('userModel')->getAuthedUser()) {
                $user = "$u->email, id $u->id, $u->role";
            }
        } catch (Exception $e) {
            $user = null;
        }
        if ($user) {
            $client->user_context(array(
                'user' => $user
            ));
        }

        try {
            $client->captureException($exception->getException());
        } catch(Exception $e) {
            $to = array();
            foreach (Kwf_Registry::get('config')->developers as $dev) {
                if (isset($dev->sendException) && $dev->sendException) {
                    $to[] = $dev->email;
                }
            }
            if ($to) {
                mail(implode('; ', $to),
                    'Error while trying to submit exception using raven',
                    $e->__toString()."\n\n---------------------------\n\nOriginal Exception:\n\n".$content
                );
            }
        }
    }
}
