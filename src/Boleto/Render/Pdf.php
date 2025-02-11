<?php

namespace Newerton\Yii2Boleto\Boleto\Render;

use Newerton\Yii2Boleto\Contracts\Boleto\Boleto as BoletoContract;
use Newerton\Yii2Boleto\Contracts\Boleto\Render\Pdf as PdfContract;
use Newerton\Yii2Boleto\Util;

class Pdf extends AbstractPdf implements PdfContract
{
    const OUTPUT_STANDARD = 'I';
    const OUTPUT_DOWNLOAD = 'D';
    const OUTPUT_SAVE = 'F';
    const OUTPUT_STRING = 'S';

    private $PadraoFont = 'Arial';
    /**
     * @var BoletoContract[]
     */
    private $boleto = array();

    /**
     * @var bool
     */
    private $print = false;

    /**
     * @var bool
     */
    private $showInstrucoes = true;

    /**
     * @var bool
     */
    private $showComprovante = false;

    private $desc = 2; // tamanho célula descrição
    private $cell = 3; // tamanho célula dado
    private $fdes = 5; // tamanho fonte descrição
    private $fcel = 6; // tamanho fonte célula
    private $small = 0.2; // tamanho barra fina
    private $totalBoletos = 0;

    public function __construct()
    {
        parent::__construct('P', 'mm', 'A4');
        $this->SetAutoPageBreak(false);
        $this->SetLeftMargin(20);
        $this->SetTopMargin(15);
        $this->SetRightMargin(20);
        $this->SetLineWidth($this->small);
    }

    /**
     * @param integer $i
     *
     * @return $this
     */
    protected function instrucoes($i)
    {
        $this->SetFont($this->PadraoFont, '', 8);
        if ($this->totalBoletos > 1) {
            $this->SetAutoPageBreak(true);
            $this->SetY(5);
            $this->Cell(30, 10, date('d/m/Y H:i:s'));
            // $this->Cell(0, 10, "Boleto " . ($i + 1) . " de " . $this->totalBoletos, 0, 1, 'R');
        }

        $this->SetFont($this->PadraoFont, 'B', 8);
        if ($this->showInstrucoes) {
            $this->Cell(0, 5, $this->_('Instruções de Impressão'), 0, 1, 'C');
            $this->Ln(5);
            $this->SetFont($this->PadraoFont, '', 6);
            $this->Cell(0, $this->desc, $this->_('- Imprima em impressora jato de tinta (ink jet) ou laser em qualidade normal ou alta (Não use modo econômico).'), 0, 1, 'L');
            $this->Cell(0, $this->desc, $this->_('- Utilize folha A4 (210 x 297 mm) ou Carta (216 x 279 mm) e margens mínimas à esquerda e à direita do formulário.'), 0, 1, 'L');
            $this->Cell(0, $this->desc, $this->_('- Corte na linha indicada. Não rasure, risque, fure ou dobre a região onde se encontra o código de barras.'), 0, 1, 'L');
            $this->Cell(0, $this->desc, $this->_('- Caso não apareça o código de barras no final, clique em F5 para atualizar esta tela.'), 0, 1, 'L');
            $this->Cell(0, $this->desc, $this->_('- Caso tenha problemas ao imprimir, copie a seqüencia numérica abaixo e pague no caixa eletrônico ou no internet banking:'), 0, 1, 'L');
            $this->Ln(6);

            $this->SetFont($this->PadraoFont, '', $this->fcel);
            $this->Cell(25, $this->cell, $this->_('Linha Digitável: '), 0, 0);
            $this->SetFont($this->PadraoFont, 'B', $this->fcel);
            $this->Cell(0, $this->cell, $this->_($this->boleto[$i]->getLinhaDigitavel()), 0, 1);
            $this->SetFont($this->PadraoFont, '', $this->fcel);
            $this->Cell(25, $this->cell, $this->_('Número: '), 0, 0);
            $this->SetFont($this->PadraoFont, 'B', $this->fcel);
            $this->Cell(0, $this->cell, $this->_($this->boleto[$i]->getNumero()), 0, 1);
            $this->SetFont($this->PadraoFont, '', $this->fcel);
            $this->Cell(25, $this->cell, $this->_('Valor: '), 0, 0);
            $this->SetFont($this->PadraoFont, 'B', $this->fcel);
            $this->Cell(0, $this->cell, $this->_(Util::nReal($this->boleto[$i]->getValor())), 0, 1);
            $this->SetFont($this->PadraoFont, '', $this->fcel);
        }

        if (!$this->showComprovante) {
            $this->traco('Recibo do Pagador', 4);
        }
        return $this;
    }
    
    /**
     * @param integer $i
     *
     * @return $this
     */
    protected function comprovante($i)
    {
        $this->SetFont($this->PadraoFont, 'B', 8);
        if ($this->showComprovante) {
            $this->Image($this->boleto[$i]->getLogoBanco(), 20, ($this->GetY() - 2), 28);
            $this->Cell(29, 6, '', 'B');
            $this->SetFont('', 'B', 13);
            $this->Cell(15, 6, $this->boleto[$i]->getCodigoBancoComDv(), 'LBR', 0, 'C');
            $this->Ln(6);

            $this->SetFont($this->PadraoFont, '', $this->fdes);
            $this->Cell(60, $this->desc, $this->_('Beneficiário'), 'TLR');
            $this->Cell(35, $this->desc, $this->_('Agencia/Codigo do beneficiário'), 'TR');
            $this->Cell(75, $this->desc, $this->_('Motivos de não entregar (Para uso da empresa entregadora)'), 'TR', 1, 'C');

            $this->SetFont($this->PadraoFont, 'B', $this->fcel);

            $this->textFitCell(60, $this->cell, $this->_($this->boleto[$i]->getBeneficiario()->getNome()), 'LR', 0, 'L');
            $this->Cell(35, $this->cell, $this->_($this->boleto[$i]->getAgenciaCodigoBeneficiario()), 'R');
            $this->Cell(75, $this->cell, $this->_(''), 'R', 1);

            $this->SetFont($this->PadraoFont, '', $this->fdes);
            $this->Cell(60, $this->desc, $this->_('Pagador'), 'TLR');
            $this->Cell(35, $this->desc, $this->_('Nosso Numero'), 'TR');
            $this->Cell(75, $this->desc, $this->_(''), 'R', 1);

            $this->SetFont($this->PadraoFont, 'B', $this->fcel);
            $this->Cell(60, $this->cell, $this->_($this->boleto[$i]->getPagador()->getNome()), 'LR');
            $this->Cell(35, $this->cell, $this->_($this->boleto[$i]->getNossoNumeroBoleto()), 'R');
            
            $this->SetFont($this->PadraoFont, '', $this->fdes);
            $this->Cell(20, $this->cell, $this->_("( ) Mudou-se"));
            $this->Cell(20, $this->cell, $this->_("( ) Ausente"));
            $this->Cell(35, $this->cell, $this->_("( ) Não existe no indicado"), 'R', 1);

            $this->SetFont($this->PadraoFont, '', $this->fdes);
            $this->Cell(19, $this->desc, $this->_('Vencimento'), 'TLR');
            $this->Cell(19, $this->desc, $this->_('N. do Documento'), 'TR');
            $this->Cell(10, $this->desc, $this->_('Espécie'), 'TR');
            $this->Cell(13, $this->desc, $this->_('Quantidade'), 'TR');
            $this->Cell(34, $this->desc, $this->_('Valor'), 'TR');
            $this->Cell(75, $this->desc, $this->_(''), 'R', 1);

            $this->SetFont($this->PadraoFont, 'B', $this->fcel);
            $this->Cell(19, $this->cell, $this->_($this->boleto[$i]->getDataVencimento()->format('d/m/Y')), 'LR');
            $this->Cell(19, $this->cell, $this->_($this->boleto[$i]->getNumeroDocumento()), 'R', 0, 'C');
            $this->Cell(10, $this->cell, $this->_($this->boleto[$i]->getEspecieDoc()), 'R', 0, 'C');
            $this->Cell(13, $this->cell, $this->_('1'), 'R', 0, 'C');
            $this->Cell(34, $this->cell, $this->_(Util::nReal($this->boleto[$i]->getValor())), 'R', 0, 'R');

            $this->SetFont($this->PadraoFont, '', $this->fdes);
            $this->Cell(20, $this->cell, $this->_("( ) Recusado"));
            $this->Cell(20, $this->cell, $this->_("( ) Não procurado"));
            $this->Cell(35, $this->cell, $this->_("( ) Endereço insuficiente"), 'R', 1);

            $this->SetFont($this->PadraoFont, '', $this->fdes);
            $this->Cell(35, $this->desc, $this->_('Recebi(emos) o bloqueto/título'), 'TLR');
            $this->Cell(19, $this->desc, $this->_('Data'), 'TR');
            $this->Cell(40, $this->desc, $this->_('Assinatura'), 'TR');
            $this->Cell(20, $this->desc, $this->_('Data'), 'TR');
            $this->Cell(56, $this->desc, $this->_('Entregador'), 'TR', 1);

            $this->SetFont($this->PadraoFont, '', $this->fdes);
            $this->Cell(35, $this->cell, $this->_('com as características acima'), 'BLR');
            $this->SetFont($this->PadraoFont, 'B', $this->fcel);
            $this->Cell(19, $this->cell, $this->_($this->boleto[$i]->getDataDocumento()->format('d/m/Y')), 'BLR');
            $this->Cell(40, $this->cell, $this->_(''), 'BLR');
            $this->Cell(20, $this->cell, $this->_(''), 'BLR');
            $this->Cell(56, $this->cell, $this->_(''), 'BLR', 1);

            $pulaLinha = 1;

            $this->traco('Recibo do Pagador', $pulaLinha, 10);
        }
        
        return $this;
    }

    /**
     * @param integer $i
     *
     * @return $this
     */
    protected function logoEmpresa($i)
    {
        $this->Ln(2);
        $this->SetFont($this->PadraoFont, '', $this->fdes);

        $logo = preg_replace('/\&.*/', '', $this->boleto[$i]->getLogo());
        $ext = pathinfo($logo, PATHINFO_EXTENSION);

        $this->Image($this->boleto[$i]->getLogo(), 20, ($this->GetY()), 0, 12, $ext);
        $this->Cell(56);
        $this->Cell(0, $this->desc, $this->_($this->boleto[$i]->getBeneficiario()->getNome()), 0, 1);
        $this->Cell(56);
        $this->Cell(0, $this->desc, $this->_($this->boleto[$i]->getBeneficiario()->getDocumento(), '##.###.###/####-##'), 0, 1);
        $this->Cell(56);
        $this->Cell(0, $this->desc, $this->_($this->boleto[$i]->getBeneficiario()->getEndereco()), 0, 1);
        $this->Cell(56);
        $this->Cell(0, $this->desc, $this->_($this->boleto[$i]->getBeneficiario()->getCepCidadeUf()), 0, 1);
        $this->Ln(8);

        return $this;
    }

    /**
     * @param integer $i
     *
     * @return $this
     */
    protected function Topo($i)
    {
        $this->Image($this->boleto[$i]->getLogoBanco(), 20, ($this->GetY() - 2), 28);
        $this->Cell(29, 6, '', 'B');
        $this->SetFont('', 'B', 13);
        $this->Cell(15, 6, $this->boleto[$i]->getCodigoBancoComDv(), 'LBR', 0, 'C');
        $this->SetFont('', 'B', 10);
        $this->Cell(0, 6, $this->boleto[$i]->getLinhaDigitavel(), 'B', 1, 'R');

        $this->SetFont($this->PadraoFont, '', $this->fdes);
        $this->Cell(75, $this->desc, $this->_('Beneficiário'), 'TLR');
        $this->Cell(35, $this->desc, $this->_('Agencia/Codigo do beneficiário'), 'TR');
        $this->Cell(10, $this->desc, $this->_('Espécie'), 'TR');
        $this->Cell(15, $this->desc, $this->_('Quantidade'), 'TR');
        $this->Cell(35, $this->desc, $this->_('Nosso Numero'), 'TR', 1);

        $this->SetFont($this->PadraoFont, 'B', $this->fcel);

        $this->textFitCell(75, $this->cell, $this->_($this->boleto[$i]->getBeneficiario()->getNome()), 'LR', 0, 'L');

        $this->Cell(35, $this->cell, $this->_($this->boleto[$i]->getAgenciaCodigoBeneficiario()), 'R');
        $this->Cell(10, $this->cell, $this->_('R$'), 'R');
        $this->Cell(15, $this->cell, $this->_(''), 'R');
        $this->Cell(35, $this->cell, $this->_($this->boleto[$i]->getNossoNumeroBoleto()), 'R', 1, 'R');

        $this->SetFont($this->PadraoFont, '', $this->fdes);
        $this->Cell(50, $this->desc, $this->_('Número do Documento'), 'TLR');
        $this->Cell(40, $this->desc, $this->_('CPF/CNPJ'), 'TR');
        $this->Cell(30, $this->desc, $this->_('Vencimento'), 'TR');
        $this->Cell(50, $this->desc, $this->_('Valor do Documento'), 'TR', 1);

        $this->SetFont($this->PadraoFont, 'B', $this->fcel);
        $this->Cell(50, $this->cell, $this->_($this->boleto[$i]->getNumeroDocumento()), 'LR');
        $this->Cell(40, $this->cell, $this->_($this->boleto[$i]->getBeneficiario()->getDocumento(), '##.###.###/####-##'), 'R');
        $this->Cell(30, $this->cell, $this->_($this->boleto[$i]->getDataVencimento()->format('d/m/Y')), 'R');
        $this->Cell(50, $this->cell, $this->_(Util::nReal($this->boleto[$i]->getValor())), 'R', 1, 'R');

        $this->SetFont($this->PadraoFont, '', $this->fdes);
        $this->Cell(30, $this->desc, $this->_('(-) Descontos/Abatimentos'), 'TLR');
        $this->Cell(30, $this->desc, $this->_('(-) Outras Deduções'), 'TR');
        $this->Cell(30, $this->desc, $this->_('(+) Mora Multa'), 'TR');
        $this->Cell(30, $this->desc, $this->_('(+) Acréscimos'), 'TR');
        $this->Cell(50, $this->desc, $this->_('(=) Valor Cobrado'), 'TR', 1);

        $this->SetFont($this->PadraoFont, 'B', $this->fcel);
        $this->Cell(30, $this->cell, $this->_(''), 'LR');
        $this->Cell(30, $this->cell, $this->_(''), 'R');
        $this->Cell(30, $this->cell, $this->_(''), 'R');
        $this->Cell(30, $this->cell, $this->_(''), 'R');
        $this->Cell(50, $this->cell, $this->_(''), 'R', 1, 'R');

        $this->SetFont($this->PadraoFont, '', $this->fdes);
        $this->Cell(0, $this->desc, $this->_('Pagador'), 'TLR', 1);

        $this->SetFont($this->PadraoFont, 'B', $this->fcel);
        $this->Cell(0, $this->cell, $this->_($this->boleto[$i]->getPagador()->getNomeDocumento()), 'BLR', 1);

        $this->SetFont($this->PadraoFont, '', $this->fdes);
        $this->Cell(100, $this->desc, $this->_('Demonstrativo'), 0, 0, 'L');
        $this->Cell(0, $this->desc, $this->_('Autenticação mecânica'), 0, 1, 'R');
        $this->Ln(2);

        $pulaLinha = 26;

        $this->SetFont($this->PadraoFont, 'B', $this->fcel);
        if (count($this->boleto[$i]->getDescricaoDemonstrativo()) > 0) {
            $pulaLinha = $this->listaLinhas($this->boleto[$i]->getDescricaoDemonstrativo(), $pulaLinha);
        }

        $this->traco('Corte na linha pontilhada', $pulaLinha, 10);

        return $this;
    }

    /**
     * @param integer $i
     *
     * @return $this
     */
    protected function Carne($i)
    {
        $this->SetX(0);
        $this->SetLeftMargin(13);
        
        $this->Image($this->boleto[$i]->getLogoBanco(), 13, ($this->GetY() - 2), 22);
        $this->Cell(22, 6, '', 'B');
        $this->SetFont($this->PadraoFont, 'B', 13);
        $this->Cell(18, 6, $this->boleto[$i]->getCodigoBancoComDv(), 'L', 1, 'L');

        $this->SetFont($this->PadraoFont, '', $this->fdes);
        $this->Cell(20, $this->desc, 'Parcela/Plano', 'TLR');
        $this->Cell(20, $this->desc, $this->_('Vencimento'), 'TLR', 1);

        $this->SetFont($this->PadraoFont, 'B', $this->fcel);
        $this->Cell(20, $this->cell, '001', 'LR');
        $this->Cell(20, $this->cell, $this->_($this->boleto[$i]->getDataVencimento()->format('d/m/Y')), 'LR', 1, 'L');

        $this->SetFont($this->PadraoFont, '', $this->fdes);
        $this->Cell(40, $this->desc, $this->_('Agência/Código beneficiário'), 'TLR', 1);

        $this->SetFont($this->PadraoFont, 'B', $this->fcel);
        $this->Cell(40, $this->cell, $this->_($this->boleto[$i]->getAgenciaCodigoBeneficiario()), 'LR', 1, 'C');

        $this->SetFont($this->PadraoFont, '', $this->fdes);
        $this->Cell(40, $this->desc, $this->_('Espécie'), 'TLR', 1);

        $this->SetFont($this->PadraoFont, 'B', $this->fcel);
        $this->Cell(40, $this->cell, $this->_('R$'), 'LR', 1);

        $this->SetFont($this->PadraoFont, '', $this->fdes);
        $this->Cell(40, $this->desc, $this->_('Quantidade'), 'TLR', 1);
        $this->Cell(40, $this->desc, $this->_(''), 'LR', 1);

        $this->SetFont($this->PadraoFont, '', $this->fdes);
        $this->Cell(40, $this->desc, $this->_('(=) Valor Documento'), 'TLR', 1);

        $this->SetFont($this->PadraoFont, 'B', $this->fcel);
        $this->Cell(40, $this->cell, $this->_(Util::nReal($this->boleto[$i]->getValor())), 'LR', 1, 'R');

        $this->SetFont($this->PadraoFont, '', $this->fdes);
        $this->Cell(40, $this->desc, $this->_('(-) Desconto / Abatimentos)'), 'TLR', 1);

        $this->SetFont($this->PadraoFont, 'B', $this->fcel);
        $this->Cell(40, $this->cell, $this->_(Util::nReal($this->boleto[$i]->getDesconto())), 'LR', 1, 'R');

        $this->SetFont($this->PadraoFont, '', $this->fdes);
        $this->Cell(40, $this->desc, $this->_('(-) Outras deduções'), 'TLR', 1);
        $this->Cell(40, $this->desc, $this->_(''), 'LR', 1);

        $this->Cell(40, $this->desc, $this->_('(+) Mora / Multa'), 'TLR', 1);
        $this->Cell(40, $this->desc, $this->_(''), 'LR', 1);

        $this->Cell(40, $this->desc, $this->_('(+) Outros acréscimos'), 'TLR', 1);
        $this->Cell(40, $this->desc, $this->_(''), 'LR', 1);

        $this->Cell(40, $this->desc, $this->_('(=) Valor cobrado'), 'TLR', 1);
        $this->Cell(40, $this->desc, $this->_(''), 'LR', 1);

        $this->Cell(40, $this->desc, $this->_('Nosso número'), 'TLR', 1);
        $this->SetFont($this->PadraoFont, 'B', $this->fcel);
        $this->Cell(40, $this->cell, $this->_($this->boleto[$i]->getNossoNumeroBoleto()), 'LR', 1, 'C');

        $this->SetFont($this->PadraoFont, '', $this->fdes);
        $this->Cell(40, $this->desc, $this->_('Nº documento'), 'TLR', 1);

        $this->SetFont($this->PadraoFont, 'B', $this->fcel);
        $this->Cell(40, $this->cell, $this->_($this->boleto[$i]->getNumeroDocumento()), 'LR', 1);
        
        $this->SetFont($this->PadraoFont, '', $this->fdes);
        $this->Cell(40, $this->desc, $this->_('Pagador'), 'TLR', 1);

        $this->SetFont($this->PadraoFont, 'B', $this->fcel);
        $this->Cell(40, $this->cell, substr($this->_($this->boleto[$i]->getPagador()->getNome()), 0, 26), 'LR', 2);
        $this->SetFont($this->PadraoFont, 'B', $this->fcel);
        $this->Cell(40, $this->cell, substr($this->_($this->boleto[$i]->getPagador()->getNome()), 26, 26), 'LBR', 2);
        
        $this->Cell(40, $this->cell, 'Recibo do Sacado', '', 1);

        return $this;
    }

    /**
     * @param integer $i
     *
     * @return $this
     */
    protected function Bottom($i)
    {
        if ($i % 3 == 0) {
            $this->SetY(14, false);
        } else {
            $this->SetY($this->GetY()-71, false);
        }
        
        $this->SetLeftMargin(60);

        $this->Image($this->boleto[$i]->getLogoBanco(), 60, ($this->GetY() - 1), 28);
        $this->Cell(30, 6, '', 'B');
        $this->SetFont($this->PadraoFont, 'B', 13);
        $this->Cell(15, 6, $this->boleto[$i]->getCodigoBancoComDv(), 'LBR', 0, 'C');
        $this->SetFont($this->PadraoFont, 'B', 9);
        $this->Cell(0, 6, $this->boleto[$i]->getLinhaDigitavel(), '', 1, 'L');

        $this->SetFont($this->PadraoFont, '', $this->fdes);
        $this->Cell(100, $this->desc, $this->_('Local de pagamento'), 'TLR');
        $this->Cell(30, $this->desc, $this->_('Vencimento'), 'TR', 1);

        $this->SetFont($this->PadraoFont, 'B', $this->fcel);
        $this->Cell(100, $this->cell, $this->_($this->boleto[$i]->getLocalPagamento()), 'LR');
        $this->Cell(30, $this->cell, $this->_($this->boleto[$i]->getDataVencimento()->format('d/m/Y')), 'R', 1, 'R');

        $this->SetFont($this->PadraoFont, '', $this->fdes);
        $this->Cell(100, $this->desc, $this->_('Beneficiário'), 'TLR');
        $this->Cell(30, $this->desc, $this->_('Agência/Código beneficiário'), 'TR', 1);

        $this->SetFont($this->PadraoFont, 'B', $this->fcel);
        $this->Cell(100, $this->cell, $this->_($this->boleto[$i]->getBeneficiario()->getNomeDocumento()), 'LR');
        $this->Cell(30, $this->cell, $this->_($this->boleto[$i]->getAgenciaCodigoBeneficiario()), 'R', 1, 'R');

        $this->SetFont($this->PadraoFont, '', $this->fdes);
        $this->Cell(25, $this->desc, $this->_('Data do documento'), 'TLR');
        $this->Cell(32, $this->desc, $this->_('Número do documento'), 'TR');
        $this->Cell(13, $this->desc, $this->_('Espécie Doc.'), 'TR');
        $this->Cell(10, $this->desc, $this->_('Aceite'), 'TR');
        $this->Cell(20, $this->desc, $this->_('Data processamento'), 'TR');
        $this->Cell(30, $this->desc, $this->_('Nosso número'), 'TR', 1);

        $this->SetFont($this->PadraoFont, 'B', $this->fcel);
        $this->Cell(25, $this->cell, $this->_($this->boleto[$i]->getDataDocumento()->format('d/m/Y')), 'LR');
        $this->Cell(32, $this->cell, $this->_($this->boleto[$i]->getNumeroDocumento()), 'R');
        $this->Cell(13, $this->cell, $this->_($this->boleto[$i]->getEspecieDoc()), 'R');
        $this->Cell(10, $this->cell, $this->_($this->boleto[$i]->getAceite()), 'R');
        $this->Cell(20, $this->cell, $this->_($this->boleto[$i]->getDataProcessamento()->format('d/m/Y')), 'R');
        $this->Cell(30, $this->cell, $this->_($this->boleto[$i]->getNossoNumeroBoleto()), 'R', 1, 'R');

        $this->SetFont($this->PadraoFont, '', $this->fdes);

        if (isset($this->boleto[$i]->variaveis_adicionais['esconde_uso_banco']) && $this->boleto[$i]->variaveis_adicionais['esconde_uso_banco']) {
            $this->Cell(45, $this->desc, $this->_('Carteira'), 'TLR');
        } else {
            $cip = isset($this->boleto[$i]->variaveis_adicionais['mostra_cip']) && $this->boleto[$i]->variaveis_adicionais['mostra_cip'];

            $this->Cell(($cip ? 23 : 30), $this->desc, $this->_('Uso do Banco'), 'TLR');
            if ($cip) {
                $this->Cell(7, $this->desc, $this->_('CIP'), 'TLR');
            }
            $this->Cell(15, $this->desc, $this->_('Carteira'), 'TR');
        }

        $this->Cell(12, $this->desc, $this->_('Espécie'), 'TR');
        $this->Cell(18, $this->desc, $this->_('Quantidade'), 'TR');
        $this->Cell(25, $this->desc, $this->_('Valor'), 'TR');
        $this->Cell(30, $this->desc, $this->_('(=) Valor Documento'), 'TR', 1);

        $this->SetFont($this->PadraoFont, 'B', $this->fcel);

        if (isset($this->boleto[$i]->variaveis_adicionais['esconde_uso_banco']) && $this->boleto[$i]->variaveis_adicionais['esconde_uso_banco']) {
            $this->TextFitCell(45, $this->cell, $this->_($this->boleto[$i]->getCarteiraNome()), 'LR', 0, 'L');
        } else {
            $cip = isset($this->boleto[$i]->variaveis_adicionais['mostra_cip']) && $this->boleto[$i]->variaveis_adicionais['mostra_cip'];
            $this->Cell(($cip ? 23 : 30), $this->cell, $this->_(''), 'LR');
            if ($cip) {
                $this->Cell(7, $this->cell, $this->_($this->boleto[$i]->getCip()), 'LR');
            }
            $this->Cell(15, $this->cell, $this->_(strtoupper($this->boleto[$i]->getCarteiraNome())), 'R');
        }

        $this->Cell(12, $this->cell, $this->_('R$'), 'R');
        $this->Cell(18, $this->cell, $this->_(''), 'R');
        $this->Cell(25, $this->cell, $this->_(''), 'R');
        $this->Cell(30, $this->cell, $this->_(Util::nReal($this->boleto[$i]->getValor())), 'R', 1, 'R');

        $this->SetFont($this->PadraoFont, '', $this->fdes);
        $this->Cell(100, $this->desc, $this->_("Instruções de responsabilidade do beneficiário. Qualquer dúvida sobre este boleto, contate o beneficiário"), 'TLR');
        $this->Cell(30, $this->desc, $this->_('(-) Desconto / Abatimentos)'), 'TR', 1);

        $xInstrucoes = $this->GetX();
        $yInstrucoes = $this->GetY();

        $this->SetFont($this->PadraoFont, '', $this->fdes);
        $this->Cell(100, $this->cell, $this->_(''), 'LR');

        $this->SetFont($this->PadraoFont, 'B', $this->fcel);
        $this->Cell(30, $this->cell, $this->_(Util::nReal($this->boleto[$i]->getDesconto())), 'R', 1, 'R');
        // $this->Cell(30, $this->cell, $this->_(''), 'R', 1);

        $this->SetFont($this->PadraoFont, '', $this->fdes);

        $this->Cell(100, $this->desc, $this->_(''), 'LR');
        $this->Cell(30, $this->desc, $this->_('(-) Outras deduções'), 'TR', 1);

        $this->Cell(100, $this->cell, $this->_(''), 'LR');
        $this->Cell(30, $this->cell, $this->_(''), 'R', 1);

        $this->Cell(100, $this->desc, $this->_(''), 'LR');
        $this->Cell(30, $this->desc, $this->_('(+) Mora / Multa'), 'TR', 1);

        $this->Cell(100, $this->cell, $this->_(''), 'LR');
        $this->Cell(30, $this->cell, $this->_(''), 'R', 1);

        $this->Cell(100, $this->desc, $this->_(''), 'LR');
        $this->Cell(30, $this->desc, $this->_('(+) Outros acréscimos'), 'TR', 1);

        $this->Cell(100, $this->cell, $this->_(''), 'LR');
        $this->Cell(30, $this->cell, $this->_(''), 'R', 1);

        $this->Cell(100, $this->desc, $this->_(''), 'LR');
        $this->Cell(30, $this->desc, $this->_('(=) Valor cobrado'), 'TR', 1);

        $this->Cell(100, $this->cell, $this->_(''), 'BLR');
        $this->Cell(30, $this->cell, $this->_(''), 'BR', 1);

        $this->SetFont($this->PadraoFont, '', $this->fdes);
        $this->Cell(130, $this->desc, $this->_('Pagador'), 'LR', 1);

        $this->SetFont($this->PadraoFont, 'B', $this->fcel);
        $this->Cell(130, $this->cell, $this->_($this->boleto[$i]->getPagador()->getNomeDocumento()), 'LR', 1);
        $this->Cell(130, $this->cell, $this->_(trim($this->boleto[$i]->getPagador()->getEndereco() . ' - ' . $this->boleto[$i]->getPagador()->getBairro()), ' -'), 'LR', 1);
        $this->Cell(130, $this->cell, $this->_($this->boleto[$i]->getPagador()->getCepCidadeUf()), 'LR', 1);

        $this->SetFont($this->PadraoFont, '', $this->fdes);
        $this->Cell(100, $this->cell, $this->_(''), 'BL');
        $this->Cell(12, $this->cell, $this->_('Cód. Baixa'), 'B');
        $this->SetFont($this->PadraoFont, 'B', $this->fcel);
        $this->Cell(18, $this->cell, $this->_(''), 'BR', 1);

        $this->SetFont($this->PadraoFont, '', $this->fdes);
        $this->Cell(20, $this->desc, $this->_('Sacador/Avalista'), 0);
        $this->Cell(68, $this->desc, $this->_($this->boleto[$i]->getSacadorAvalista() ? $this->boleto[$i]->getSacadorAvalista()->getNomeDocumento() : ''), 0);
        $this->Cell(22, $this->desc, $this->_('Autenticação mecânica - Ficha de Compensação'), 0, 1);

        $xOriginal = $this->GetX();
        $yOriginal = $this->GetY();

        if (count($this->boleto[$i]->getInstrucoes()) > 0) {
            $this->SetXY($xInstrucoes, $yInstrucoes);
            $this->Ln(1);
            $this->SetFont($this->PadraoFont, 'B', $this->fcel);

            $this->listaLinhas($this->boleto[$i]->getInstrucoes(), 0);

            $this->SetXY($xOriginal, $yOriginal);
        }
        return $this;
    }

    /**
     * @param      string $texto
     * @param integer $ln
     * @param integer $ln2
     */
    protected function traco($texto, $ln = null, $ln2 = null)
    {
        if ($ln == 1 || $ln) {
            $this->Ln($ln);
        }
        $this->SetFont($this->PadraoFont, '', $this->fdes);
        if ($texto) {
            $this->Cell(0, 2, $this->_($texto), 0, 1, 'R');
        }
        $this->Cell(0, 2, str_pad('-', '261', ' -', STR_PAD_RIGHT), 0, 1);
        if ($ln2 == 1 || $ln2) {
            $this->Ln($ln2);
        }
    }

    /**
     * @param integer $i
     */
    protected function codigoBarras($i)
    {
        $this->Ln(3);
        $this->Cell(0, 15, '', 0, 1, 'L');
        $this->i25($this->GetX(), $this->GetY() - 15, $this->boleto[$i]->getCodigoBarras(), 0.8, 10);
    }

    /**
     * Addiciona o boletos
     *
     * @param array $boletos
     *
     * @return $this
     */
    public function addBoletos(array $boletos)
    {
        $this->StartPageGroup();

        foreach ($boletos as $boleto) {
            $this->addBoleto($boleto);
        }

        return $this;
    }

    /**
     * Addiciona o boleto
     *
     * @param BoletoContract $boleto
     *
     * @return $this
     */
    public function addBoleto(BoletoContract $boleto)
    {
        $this->totalBoletos += 1;
        $this->boleto[] = $boleto;
        return $this;
    }

    /**
     * @return $this
     */
    public function hideInstrucoes()
    {
        $this->showInstrucoes = false;
        return $this;
    }

    /**
     * @return $this
     */
    public function showComprovante()
    {
        $this->showComprovante = true;
        return $this;
    }
    
    /**
     * @return $this
     */
    public function showPrint()
    {
        $this->print = true;
        return $this;
    }

    /**
     * função para gerar o boleto
     *
     * @param string $dest tipo de destino const BOLETOPDF_DEST_STANDARD | BOLETOPDF_DEST_DOWNLOAD | BOLETOPDF_DEST_SAVE | BOLETOPDF_DEST_STRING
     * @param null $save_path
     *
     * @return string
     * @throws \Exception
     */
    public function gerarBoleto($dest = self::OUTPUT_STANDARD, $save_path = null)
    {
        if ($this->totalBoletos == 0) {
            throw new \Exception('Nenhum Boleto adicionado');
        }

        for ($i = 0; $i < $this->totalBoletos; $i++) {
            $this->SetDrawColor('0', '0', '0');
            if ($i % 3 == 0) {
                $this->AddPage();
            } else {
                $this->traco('Corte na linha pontilhada', 0, 3);
            }
            $this->Carne($i)->Bottom($i)->codigoBarras($i);
            // $this->Carne($i);
        }
        if ($dest == self::OUTPUT_SAVE) {
            $this->Output($save_path, $dest, $this->print);
            return $save_path;
        }
        return $this->Output(\Yii::$app->security->generateRandomString(32) . '.pdf', $dest, $this->print);
    }

    /**
     * @param $lista
     * @param integer $pulaLinha
     *
     * @return int
     */
    private function listaLinhas($lista, $pulaLinha)
    {
        foreach ($lista as $d) {
            $pulaLinha -= 2;
            $this->Cell(0, $this->cell - 0.2, $this->_(preg_replace('/(%)/', '%$1', $d)), 0, 1);
        }

        return $pulaLinha;
    }
}
