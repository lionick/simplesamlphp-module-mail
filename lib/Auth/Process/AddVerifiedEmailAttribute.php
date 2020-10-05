<?php
/**
 * Authentication processing filter for generating attribute containing
 * verified email addresses.
 * 
 * Example configuration:
 *
 *    authproc = array(
 *       ...
 *       '61' => array(
 *            'class' => 'mail:AddVerifiedEmailAttribute',
 *            'emailAttribute' => 'email', // Optional, defaults to 'mail'
 *            'verifiedEmailAttribute' => 'verifiedEmail', // Optional, defaults to 'voPersonVerifiedEmail'
 *            'replace' => true,   // Optional, defaults to false 
 *       ),
 *
 * @author Nicolas Liampotis <nliam@grnet.gr>
 * @package SimpleSAMLphp
 */

class sspmod_mail_Auth_Process_AddVerifiedEmailAttribute extends SimpleSAML_Auth_ProcessingFilter
{

    private $emailAttribute = 'mail';

    private $verifiedEmailAttribute = 'voPersonVerifiedEmail';

    private $idpEntityIdIncludeList = array();

    private $replace = false;

    /**
     * Initialize this filter, parse configuration
     *
     * @param array $config Configuration information about this filter.
     * @param mixed $reserved For future use.
     *
     * @throws Exception If the configuration of the filter is wrong.
     */
    public function __construct($config, $reserved)
    {
        parent::__construct($config, $reserved);
        assert('is_array($config)');


        if (array_key_exists('emailAttribute', $config)) {
            if (!is_string($config['emailAttribute'])) {
                SimpleSAML_Logger::error("[mail:AddVerifiedEmailAttribute] Configuration error: 'emailAttribute' not a string literal");
                throw new Exception(
                    "AddVerifiedEmailAttribute configuration error: 'emailAttribute' not a string literal");
            }
            $this->emailAttribute = $config['emailAttribute'];
        }

        if (array_key_exists('verifiedEmailAttribute', $config)) {
            if (!is_string($config['verifiedEmailAttribute'])) {
                SimpleSAML_Logger::error("[mail:AddVerifiedEmailAttribute] Configuration error: 'verifiedEmailAttribute' not a string literal");
                throw new Exception(
                    "AddVerifiedEmailAttribute configuration error: 'verifiedEmailAttribute' not a string literal");
            }
            $this->verifiedEmailAttribute = $config['verifiedEmailAttribute'];
        }

        if (array_key_exists('idpEntityIdIncludeList', $config)) {
            if (!is_array($config['idpEntityIdIncludeList'])) {
                SimpleSAML_Logger::error("[mail:AddVerifiedEmailAttribute] Configuration error: 'idpEntityIdIncludeList' not an array");
                throw new Exception(
                    "AddVerifiedEmailAttribute configuration error: 'idpEntityIdIncludeList' not an array");
            }
            $this->idpEntityIdIncludeList = $config['idpEntityIdIncludeList'];
        }

        if (array_key_exists('replace', $config)) {
            if (!is_bool($config['replace'])) {
                SimpleSAML_Logger::error("[mail:AddVerifiedEmailAttribute] Configuration error: 'replace' not a boolean");
                throw new Exception(
                    "AddVerifiedEmailAttribute configuration error: 'replace' not a boolean value");
            }
            $this->replace = $config['replace'];
        }

    }

    /**
     * Apply filter to rename attributes.
     *
     * @param array &$state The current state.
     */
    public function process(&$state)
    {
        assert(is_array($state));
        assert(array_key_exists('Attributes', $state));

        // Nothing to do if email attribute is missing
        if (empty($state['Attributes'][$this->emailAttribute])) {
            SimpleSAML_Logger::debug("[mail:AddVerifiedEmailAttribute] process: Cannot generate " . $this->verifiedEmailAttribute . " attribute: " . $this->emailAttribute . " attribute is missing");
            return;
        }
        SimpleSAML_Logger::debug("[mail:AddVerifiedEmailAttribute] process: input: " . $this->emailAttribute . " = " . var_export($state['Attributes'][$this->emailAttribute], true));

        // Nothing to do if verified email attribute already exists and replace is set to false
        if (!empty($state['Attributes'][$this->verifiedEmailAttribute]) && !$this->replace) {
            SimpleSAML_Logger::debug("[mail:AddVerifiedEmailAttribute] process: Cannot replace existing " . $this->verifiedEmailAttribute . " attribute: replace is set to false");
            return;
        }

        // Retrieve idpEntityId
        $idpEntityId = $this->getIdpEntityId($state);
        // Check if idpEntityId is empty.
        // This should never happen - but if it does log an error message
        if (empty($idpEntityId)) {
            SimpleSAML_Logger::error("[mail:AddVerifiedEmailAttribute] process: Failed to retrieve idpEntityId");
            return;
        }
        SimpleSAML_Logger::debug("[mail:AddVerifiedEmailAttribute] process: input: idpEntityId = " . var_export($idpEntityId, true));

        // Nothing to do if idpEntityId not in include list
        if (!in_array($idpEntityId, $this->idpEntityIdIncludeList)) {
            SimpleSAML_Logger::debug("[mail:AddVerifiedEmailAttribute] process: Will not generate " . $this->verifiedEmailAttribute . " attribute for IdP " . $idpEntityId);
            return;
        }

        // Add verifiedEmailAttribute to state attributes
        $state['Attributes'][$this->verifiedEmailAttribute] = $state['Attributes'][$this->emailAttribute];
        SimpleSAML_Logger::info("[mail:AddVerifiedEmailAttribute] process: Added " . $this->verifiedEmailAttribute . " attribute");
        SimpleSAML_Logger::debug("[mail:AddVerifiedEmailAttribute] process: output: " . $this->verifiedEmailAttribute . " = " . var_export($state['Attributes'][$this->verifiedEmailAttribute], true));
        }

    }

    private function getIdpEntityId($state)
    {
	if (!empty($state['saml:sp:IdP'])) {
            return $state['saml:sp:IdP'];
        } else if (!empty($state['Source']['entityid'])) {
            return $state['Source']['entityid'];
        }

        return null;
    }

}

