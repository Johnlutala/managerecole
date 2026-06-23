<?php 

namespace App\Dto\Api;

use Symfony\Component\Validator\Constraints as Assert;

class MerchantConfigPayload
{
    #[Assert\NotBlank(message: "Code marchand obligatoire")]
    private string $shortcode;


    public function __construct(
        string $shortcode_,
    ) {
        $this->shortcode = $shortcode_;
    }

}