<?php

namespace Apility\Payment\View\Components;

use chillerlan\QRCode\QRCode as QrCodeGenerator;

use Illuminate\Support\HtmlString;
use Illuminate\View\Component;

class QrCode extends Component
{
    public ?string $string;
    public bool $label;

    public function __construct(?string $string = null, bool $label = true)
    {
        $this->string = $string;
        $this->label = $label;
    }

    public function qr(): HtmlString
    {
        $qr = new QrCodeGenerator();
        return new HtmlString($qr->render($this->string));
    }

    public function shouldRender()
    {
        return $this->string !== null;
    }

    public function render()
    {
        return view('payment::components.qr-code', [
            'qr' => $this->qr()
        ]);
    }
}
