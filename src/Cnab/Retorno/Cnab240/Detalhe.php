<?php
namespace Cnab\Retorno\Cnab240;

class Detalhe extends \Cnab\Format\Linha implements \Cnab\Retorno\IDetalhe
{
    public $codigo_banco;
    public $arquivo;

    public $segmento_t;
    public $segmento_u;
    public $segmento_w;

	public function __construct(\Cnab\Retorno\IArquivo $arquivo)
	{
		$this->codigo_banco = $arquivo->codigo_banco;
        $this->arquivo = $arquivo;
	}
	
	/**
	 * Retorno se é para dar baixa no boleto
	 * @return Boolean
	 */
	public function isBaixa()
    {
        $codigo_movimento = $this->segmento_t->codigo_movimento;
	    return self::isBaixaStatic($codigo_movimento);
	}

	public static function isBaixaStatic($codigo_movimento)
	{
		$tipo_baixa = array(6, 9, 17, 25);
		$codigo_movimento = (int)$codigo_movimento;
		if(in_array($codigo_movimento, $tipo_baixa))
			return true;
		else
			return false;
	}

	/**
	 * Retorno se é uma baixa rejeitada
	 * @return Boolean
	 */
	public function isBaixaRejeitada()
	{
		$tipo_baixa = array(3, 26, 30);
		$codigo_movimento = (int)$this->segmento_t->codigo_movimento;
		if(in_array($codigo_movimento, $tipo_baixa))
			return true;
		else
			return false;
	}

	/**
	 * Identifica o tipo de detalhe, se por exemplo uma taxa de manutenção
	 * @return Integer
	 */
	public function getCodigo()
	{
		return (int)$this->segmento_t->codigo_movimento;
	}
	
	/**
	 * Retorna o valor recebido em conta
	 * @return Double
	 */
	public function getValorRecebido()
	{
		return $this->segmento_u->valor_liquido;
	}

	/**
	 * Retorna o valor do título
	 * @return Double
	 */
	public function getValorTitulo()
	{
		return $this->segmento_t->valor_titulo;
	}

	/**
	 * Retorna o valor do pago
	 * @return Double
	 */
	public function getValorPago()
	{
		return $this->segmento_u->valor_pago;
	}

	/**
	 * Retorna o valor da tarifa
	 * @return Double
	 */
	public function getValorTarifa()
	{
		return $this->segmento_t->valor_tarifa;
	}

	/**
	 * Retorna o valor do Imposto sobre operações financeiras
	 * @return Double
	 */
	public function getValorIOF()
	{
		return $this->segmento_u->valor_iof;
	}

	/**
	 * Retorna o valor dos descontos concedido (antes da emissão)
	 * @return Double;
	 */
	public function getValorDesconto()
	{
		return $this->segmento_u->valor_desconto;
	}

	/**
	 * Retorna o valor dos abatimentos concedidos (depois da emissão)
	 * @return Double
	 */
	public function getValorAbatimento()
	{
		return $this->segmento_u->valor_abatimento;
	}

	/**
	 * Retorna o valor de outras despesas
	 * @return Double
	 */
	public function getValorOutrasDespesas()
	{
	    return $this->segmento_u->valor_outras_despesas;
	}

	/**
	 * Retorna o valor de outros creditos
	 * @return Double
	 */
	public function getValorOutrosCreditos()
	{
	    return $this->segmento_u->valor_outros_creditos;
	}

	/**
	 * Retorna o número do documento do boleto
	 * @return String
	 */
	public function getNumeroDocumento()
	{
        $numero_documento = $this->segmento_t->numero_documento;
        if(trim($numero_documento, '0') == '')
            return null;
        return $numero_documento;
	}

	/**
	 * Retorna o nosso número do boleto
	 * @return String
	 */
	public function getNossoNumero()
	{
        $nossoNumero = $this->segmento_t->nosso_numero;

        if ($this->codigo_banco == 1) {
            $nossoNumero = preg_replace(
                '/^'.strval($this->arquivo->getCodigoConvenio()).'/',
                '',
                $nossoNumero
            );
        }

        if (in_array($this->codigo_banco, array(\Cnab\Banco::SANTANDER))) {
            // retira o dv
            $nossoNumero = substr($nossoNumero, 0, -1);
        }

        return $nossoNumero;
	}

	/**
	 * Retorna o objeto \DateTime da data de vencimento do boleto
	 * @return \DateTime
	 */
	public function getDataVencimento()
	{
		$data = $this->segmento_t->data_vencimento ? \DateTime::createFromFormat('dmY', sprintf('%08d', $this->segmento_t->data_vencimento)) : false;
        if($data)
            $data->setTime(0,0,0);
        return $data;        
	}

	/**
	 * Retorna a data em que o dinheiro caiu na conta
	 * @return \DateTime
	 */
	public function getDataCredito()
	{
		$data = $this->segmento_u->data_credito ? \DateTime::createFromFormat('dmY', sprintf('%08d', $this->segmento_u->data_credito)) : false;
        if($data)
            $data->setTime(0,0,0);
        return $data;
	}

	/**
	 * Retorna o valor de juros e mora
	 */
	public function getValorMoraMulta()
	{
		return $this->segmento_u->valor_acrescimos;
	}

	/**
	 * Retorna a data da ocorrencia, o dia do pagamento
	 * @return \DateTime
	 */
	public function getDataOcorrencia()
	{
		$data = $this->segmento_u->data_ocorrencia ? \DateTime::createFromFormat('dmY', sprintf('%08d', $this->segmento_u->data_ocorrencia)) : false;
        if($data)
            $data->setTime(0,0,0);
        return $data;
	}

	/**
	 * Retorna o número da carteira do boleto
	 * @return String
	 */
	public function getCarteira()
    {
        if($this->codigo_banco == 104)
        {
            /*
            É formado apenas o código da carteira
            Código da Carteira
            Código adotado pela FEBRABAN, para identificar a característica dos títulos dentro das modalidades de
            cobrança existentes no banco.
            ‘1’ = Cobrança Simples
            ‘3’ = Cobrança Caucionada
            ‘4’ = Cobrança Descontada
            O Código ‘1’ Cobrança Simples deve ser obrigatoriamente informado nas modalidades Cobrança Simples
            e Cobrança Rápida.
            */
            return null;
        }
        else if($this->segmento_t->existField('carteira'))
    		return $this->segmento_t->carteira;
        else
            return null;
            
	}

	/**
	 * Retorna o número da agencia do boleto
	 * @return String
	 */
	public function getAgencia()
	{
		return $this->segmento_t->agencia_mantenedora;
	}

	/**
	 * Retorna o número da agencia do boleto
	 * @return String
	 */
	public function getAgenciaDv()
	{
		return $this->segmento_t->agencia_dv;
	}
	
	/**
	 * Retorna a agencia cobradora
	 * @return string
	 */
	public function getAgenciaCobradora()
	{
		return $this->segmento_t->agencia_cobradora;
	}
	
	/**
	 * Retorna a o dac da agencia cobradora
	 * @return string
	 */
	public function getAgenciaCobradoraDac()
	{
		return $this->segmento_t->agencia_cobradora_dac;
	}
	
	/**
	 * Retorna o numero sequencial
	 * @return Integer;
	 */
	public function getNumeroSequencial()
	{
		return $this->segmento_t->numero_sequencial_lote;
	}

	/**
	 * Retorna o nome do código
	 * @return string
	 */
	public function getCodigoNome()
	{
        $codigo = (int)$this->getCodigo();

        $table = array(
			2 => 'Entrada confirmada',
			3 => 'Entrada rejeitada',
			4 => 'Transferência de Carteira/Entrada',
			5 => 'Transferência de Carteira/Baixa',
			6 => 'Liquidação',
			7 => 'Confirmação do Recebimento da Instrução de Desconto',
			8 => 'Confirmação do Recebimento do Cancelamento do Desconto',
			9 => 'Baixa',
			11 => 'Títulos em Carteira (Em Ser)',
			12 => 'Confirmação Recebimento Instrução de Abatimento',
			13 => 'Confirmação Recebimento Instrução de Cancelamento Abatimento',
			14 => 'Confirmação Recebimento Instrução Alteração de Vencimento',
			15 => 'Franco de Pagamento',
			17 => 'Liquidação Após Baixa ou Liquidação Título Não Registrado',
			19 => 'Confirmação Recebimento Instrução de Protesto',
			20 => 'Confirmação Recebimento Instrução de Sustação/Cancelamento de Protesto',
			23 => 'Remessa a Cartório (Aponte em Cartório)',
			24 => 'Retirada de Cartório e Manutenção em Carteira',
			25 => 'Protestado e Baixado (Baixa por Ter Sido Protestado)',
			26 => 'Instrução rejeitada',
			27 => 'Confirmação do Pedido de Alteração de Outros Dados',
			28 => 'Débito de Tarifas/Custas',
			29 => 'Ocorrências do Pagador',
			30 => 'Alteração de Dados Rejeitada',
			33 => 'Confirmação da Alteração dos Dados do Rateio de Crédito',
			34 => 'Confirmação do Cancelamento dos Dados do Rateio de Crédito',
			35 => 'Confirmação do Desagendamento do Débito Automático',
			36 => 'Confirmação de envio de e-mail/SMS',
			37 => 'Envio de e-mail/SMS rejeitado',
			38 => 'Confirmação de alteração do Prazo Limite de Recebimento (a data deve ser',
			39 => 'Confirmação de Dispensa de Prazo Limite de Recebimento',
			40 => 'Confirmação da alteração do número do título dado pelo Beneficiário',
			41 => 'Confirmação da alteração do número controle do Participante',
			42 => 'Confirmação da alteração dos dados do Pagador',
			43 => 'Confirmação da alteração dos dados do Pagadorr/Avalista',
			44 => 'Título pago com cheque devolvido',
			45 => 'Título pago com cheque compensado',
			46 => 'Instrução para cancelar protesto confirmada',
			47 => 'Instrução para protesto para fins falimentares confirmada',
			48 => 'Confirmação de instrução de transferência de carteira/modalidade de cobrança',
			49 => 'Alteração de contrato de cobrança',
			50 => 'Título pago com cheque pendente de liquidação',
			51 => 'Título DDA reconhecido pelo Pagador',
			52 => 'Título DDA não reconhecido pelo Pagador',
			53 => 'Título DDA recusado pela CIP',
			54 => 'Confirmação da Instrução de Baixa/Cancelamento de Título Negativado sem Protesto',
			55 => 'Confirmação de Pedido de Dispensa de Multa',
			56 => 'Confirmação do Pedido de Cobrança de Multa',
			57 => 'Confirmação do Pedido de Alteração de Cobrança de Juros',
			58 => 'Confirmação do Pedido de Alteração do Valor/Data de Desconto',
			59 => 'Confirmação do Pedido de Alteração do Beneficiário do Título',
			60 => 'Confirmação do Pedido de Dispensa de Juros de Mora',
			80 => 'Confirmação da instrução de negativação',
			85 => 'Confirmação de Desistência de Protesto',
			86 => 'Confirmação de cancelamento do Protesto',
        );

        if(array_key_exists($codigo, $table))
            return $table[$codigo];
        else
            return 'Desconhecido';
    }

    /**
     * Retorna o código de liquidação, normalmente usado para 
     * saber onde o cliente efetuou o pagamento
     * @return String
     */
    public function getCodigoLiquidacao() {
        // @TODO: Resgatar o código de liquidação
        return null;
    }

    public function getMotivoOcorrencia() {
        return ltrim($this->segmento_t->motivo_ocorrencia, '0');
    }

    public function getNomeSacado()
    {
        return $this->segmento_t->nome_sacado;
    }

    public function getDocumentoSacado()
    {
        return $this->segmento_t->documento_sacado;
    }

    public function getMotivoOcorrenciaNome()
	{
        $codigo = (int) $this->getCodigo();
        $motivo_ocorrencia = $this->getMotivoOcorrencia();

        $table_codigo_6 = [
            '1' => 'Por saldo',
            '2' => 'Por conta',
            '3' => 'Liquidação no guichê de caixa em dinheiro',
            '4' => 'Compensação eletrônica',
            '5' => 'Compensação convencional',
            '6' => 'Por meio eletrônico',
            '7' => 'Após feriado local',
            '8' => 'Em cartório',
            '30' => 'Liquidação no guichê de caixa em cheque',
            '31' => 'Liquidação em banco correspondente',
            '32' => 'Liquidação terminal de auto-atendimento',
            '33' => 'Liquidação na internet (Home banking)',
            '34' => 'Liquidado office banking',
            '35' => 'Liquidado correspondente em dinheiro',
            '36' => 'Liquidado correspondente em cheque',
            '37' => 'Liquidado por meio de central de atendimento (Telefone)',
            '61' => 'Liquidado via PIX',
        ];

        $table_codigo_9 = [
            '9' => 'Comandada banco',
            '10' => 'Comandada cliente arquivo',
            '11' => 'Comandada cliente on-line',
            '12' => 'Decurso prazo - cliente',
            '13' => 'Decurso prazo - banco',
            '14' => 'Protestado',
            '15' => 'Título excluído',
        ];

        $table_codigo_2_3_26 = [
            '1' => 'Código do Banco Inválido',
            '2' => 'Código do Registro Detalhe Inválido',
            '3' => 'Código do Segmento Inválido',
            '4' => 'Código de Movimento Não Permitido para Carteira',
            '5' => 'Código de Movimento Inválido',
            '6' => 'Tipo/Número de Inscrição do Beneficiário Inválidos',
            '7' => 'Agência/Conta/DV Inválido',
            '8' => 'Nosso número inválido',
            '9' => 'Nosso número duplicado',
            '10' => 'Carteira Inválida',
            '11' => 'Forma de Cadastramento do Título Inválido',
            '12' => 'Tipo de Documento Inválido',
            '13' => 'Identificação da Emissão do Boleto de Pagamento Inválida',
            '14' => 'Identificação da Distribuição do Boleto de Pagamento Inválida',
            '15' => 'Características da Cobrança Incompatíveis',
            '16' => 'Data de Vencimento Inválida',
            '17' => 'Data de Vencimento Anterior a Data de Emissão',
            '18' => 'Vencimento Fora do Prazo de Operação',
            '19' => 'Título a Cargo de Bancos Correspondentes com Vencimento Inferior a XX Dias',
            '20' => 'Valor do Título Inválido',
            '21' => 'Espécie do Título Inválida',
            '22' => 'Espécie do Título Não Permitida para a Carteira',
            '23' => 'Aceite Inválido',
            '24' => 'Data da Emissão Inválida',
            '25' => 'Data da Emissão Posterior a Data de Entrada',
            '26' => 'Código de Juros de Mora Inválido',
            '27' => 'Valor/Taxa de Juros de Mora Inválido',
            '28' => 'Código do Desconto Inválido',
            '29' => 'Valor do Desconto Maior ou Igual ao Valor do Título',
            '30' => 'Desconto a Conceder Não Confere',
            '31' => 'Concessão de Desconto - Já Existe Desconto Anterior',
            '32' => 'Valor do IOF Inválido',
            '33' => 'Valor do Abatimento Inválido',
            '34' => 'Valor do Abatimento Maior ou Igual ao Valor do Título',
            '35' => 'Valor a Conceder Não Confere',
            '36' => 'Concessão de Abatimento - Já Existe Abatimento Anterior',
            '37' => 'Código para Protesto Inválido',
            '38' => 'Prazo para Protesto Inválido',
            '39' => 'Pedido de Protesto Não Permitido para o Título',
            '40' => 'Título com Ordem de Protesto Emitida',
            '41' => 'Pedido de Cancelamento/Sustação para Títulos sem Instrução de Protesto',
            '42' => 'Código para Baixa/Devolução Inválido',
            '43' => 'Prazo para Baixa/Devolução Inválido',
            '44' => 'Código da Moeda Inválido',
            '45' => 'Nome do Pagador Não Informado',
            '46' => 'Tipo/Número de Inscrição do Pagador Inválidos',
            '47' => 'Endereço do Pagador Não Informado',
            '48' => 'CEP Inválido',
            '49' => 'CEP Sem Praça de Cobrança (Não Localizado)',
            '50' => 'CEP Referente a um Banco Correspondente',
            '51' => 'CEP incompatível com a Unidade da Federação',
            '52' => 'Unidade da Federação Inválida',
            '53' => 'Tipo/Número de Inscrição do Sacador/Avalista Inválidos',
            '54' => 'Sacador/Avalista Não Informado',
            '55' => 'Nosso número no Banco Correspondente Não Informado',
            '56' => 'Código do Banco Correspondente Não Informado',
            '57' => 'Código da Multa Inválido',
            '58' => 'Data da Multa Inválida',
            '59' => 'Valor/Percentual da Multa Inválido',
            '60' => 'Movimento para Título Não Cadastrado',
            '61' => 'Alteração da Agência Cobradora/DV Inválida',
            '62' => 'Tipo de Impressão Inválido',
            '63' => 'Entrada para Título já Cadastrado',
            '64' => 'Número da Linha Inválido',
            '65' => 'Código do Banco para Débito Inválido',
            '66' => 'Agência/Conta/DV para Débito Inválido',
            '67' => 'Dados para Débito incompatível com a Identificação da Emissão do Boleto de Pagamento',
            '68' => 'Débito Automático Agendado',
            '69' => 'Débito Não Agendado - Erro nos Dados da Remessa',
            '70' => 'Débito Não Agendado - Pagador Não Consta do Cadastro de Autorizante',
            '71' => 'Débito Não Agendado - Beneficiário Não Autorizado pelo Pagador',
            '72' => 'Débito Não Agendado - Beneficiário Não Participa da Modalidade Automático',
            '73' => 'Débito Não Agendado - Código de Moeda Diferente de Real (R$)',
            '74' => 'Débito Não Agendado - Data Vencimento Inválida',
            '75' => 'Débito Não Agendado, Conforme seu Pedido, Título Não Registrado',
            '76' => 'Débito Não Agendado, Tipo/Num. Inscrição do Debitado, Inválido',
            '77' => 'Transferência para Desconto Não Permitida para a Carteira do Título',
            '78' => 'Data Inferior ou Igual ao Vencimento para Débito Automático',
            '79' => 'Data Juros de Mora Inválido',
            '80' => 'Data do Desconto Inválida',
            '81' => 'Tentativas de Débito Esgotadas - Baixado',
            '82' => 'Tentativas de Débito Esgotadas - Pendente',
            '83' => 'Limite Excedido',
            '84' => 'Número Autorização Inexistente',
            '85' => 'Título com Pagamento Vinculado',
            '86' => 'Seu Número Inválido',
            '87' => 'e-mail/SMS enviado',
            '88' => 'e-mail Lido',
            '89' => 'e-mail/SMS devolvido - endereço de e-mail ou número do celular incorreto',
            '90' => 'e-mail devolvido - caixa postal cheia',
            '91' => 'e-mail/número do celular do Pagador não informado',
            '92' => 'Pagador optante por Boleto de Pagamento Eletrônico - e-mail não enviado',
            '93' => 'Código para emissão de Boleto de Pagamento não permite envio de e-mail',
            '94' => 'Código da Carteira inválido para envio e-mail.',
            '95' => 'Contrato não permite o envio de e-mail',
            '96' => 'Número de contrato inválido',
            '97' => 'Rejeição da alteração do prazo limite de recebimento (a data deve ser informada no campo 28.3.p)',
            '98' => 'Rejeição de dispensa de prazo limite de recebimento',
            '99' => 'Rejeição da alteração do número do título dado pelo Beneficiário',
            'A1' => 'Rejeição da alteração do número controle do participante',
            'A2' => 'Rejeição da alteração dos dados do Pagador',
            'A3' => 'Rejeição da alteração dos dados do Sacador/avalista',
            'A4' => 'Pagador DDA',
            'A5' => 'Registro Rejeitado – Título já Liquidado',
            'A6' => 'Código do Convenente Inválido ou Encerrado',
            'A7' => 'Título já se encontra na situação Pretendida',
            'A8' => 'Valor do Abatimento inválido para cancelamento',
            'A9' => 'Não autoriza pagamento parcial',
            'B1' => 'Autoriza recebimento parcial',
            'B2' => 'Valor Nominal do Título Conflitante',
            'B3' => 'Tipo de Pagamento Inválido',
            'B4' => 'Valor Máximo/Percentual Inválido',
            'B5' => 'Valor Mínimo/Percentual Inválido',
            'P1' => 'Registrado com QR Code Pix',
            'P2' => 'Registrado sem QR Code Pix',
            'P3' => 'Chave PIX – chave invalida',
            'P4' => 'Chave PIX – sem cadastro na DICT',
            'P5' => 'Chave PIX – não é compatível com o CNPJ',
            'P6' => 'Identificador (TXID) – em duplicidade',
            'P7' => 'Identificador (TXID) – inválido ou não encontrado',
            'P8' => 'Ocorrência – alterar QR Code – alteração não permitida',
            'P9' => 'ocorrência – cancela QR Code – cancelamento n]ao permitido',
        ];

        if (!in_array($codigo, [6, 9, 2, 3, 26])) {
            return null;
        }

        if ($codigo == 6 && array_key_exists($motivo_ocorrencia, $table_codigo_6)) {
            return $table_codigo_6[$motivo_ocorrencia];
        }

        if ($codigo == 9 && array_key_exists($motivo_ocorrencia, $table_codigo_9)) {
            return $table_codigo_9[$motivo_ocorrencia];
        }

        if (array_key_exists($motivo_ocorrencia, $table_codigo_2_3_26)) {
            return $table_codigo_2_3_26[$motivo_ocorrencia];
        }

        return 'Desconhecido';
    }

    /**
     * Retorna a descrição do código de liquidação, normalmente usado para 
     * saber onde o cliente efetuou o pagamento
     * @return String
     */
    public function getDescricaoLiquidacao() {
        // @TODO: Resgator descrição do código de liquidação
        return null;
    }

    public function dump()
    {
        $dump  = PHP_EOL;
        $dump .= '== SEGMENTO T ==';
        $dump .= PHP_EOL;
        $dump .= $this->segmento_t->dump();
        $dump .= '== SEGMENTO U ==';
        $dump .= PHP_EOL;
        $dump .= $this->segmento_u->dump();

        if ($this->segmento_w)
        {
            $dump .= '== SEGMENTO W ==';
            $dump .= PHP_EOL;
            $dump .= $this->segmento_w->dump();
        }

        return $dump;
    }

    public function isDDA()
    {
        // @TODO: implementar funçao isDDA no Cnab240
    }

    public function getAlegacaoPagador()
    {
        // @TODO: implementar funçao getAlegacaoPagador no Cnab240
    }
}
