<?php

namespace App\Services\MRV;

use RuntimeException;

/**
 * The EAD workbook template is not installed on this server.
 *
 * An INSTALLATION problem, not something the operator did and not something
 * they can fix: the file is issued to each deployment out of band because it
 * carries EAD's confidentiality notice (see config/mrv.php). Whoever
 * administers the deployment has to put it in place.
 *
 * Distinct from EadTemplateMismatchException because the two need opposite
 * responses — install a file, versus check the version of the file already
 * installed — and both used to reach the operator as the same sentence.
 */
class EadTemplateMissingException extends RuntimeException {}
