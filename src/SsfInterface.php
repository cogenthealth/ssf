<?php

namespace CogentHealth\Ssf;

use CogentHealth\Ssf\Claim\Claim;

interface SsfInterface
{
    /**
     * class initialization.
     *
     * @param integer $patientId
     * @return json
     */
    public static function init();

    /**
     * Get patient details from identifier.
     *
     * @param integer $patientId
     * @return json
     */
    public static function getPatientDetailById($patientId);

    /**
     * get eligibility
     *
     * @param integer $patientId
     * @return json
     */
    public static function eligibilityRequest($patientId);

    /**
     * Claim submission
     *
     * @param integer $patientId
     * @return json
     */
    public static function claimSubmission(Claim $claim);
}
