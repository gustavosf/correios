<?php

/**
 * Classe de consulta por CEP. Usar como módulo para Correios
 *
 * Uso:
 * $c = new Correios();
 * $r = $c->cep($codigo);
 * echo $r;
 * echo $r->cep;
 * echo $r->cidade;
 * ...
 *
 * $r->cep = $novo_cep;
 * echo $r;
 *
 * @author Gustavo Seganfredo
 * @package Correios
 * @version 1.0
 * @see Correios()
 */
class CEP {

	/**
	 * Código Postal
	 * @var string
	 */
	private $cep;
	
	/**
	 * Dados do cep
	 * @var array
	 */
	private $data;
	
	/**
	 * Página do webservice
	 * @var array
	 */
	private $resource = 'http://cep.republicavirtual.com.br/web_cep.php?formato=query_string&cep=';
	
	public function __construct($cep) {

		// valida o cep passado
		$regex = '/([0-9]{5}[-]?[0-9]{3})/';
		if (!preg_match($regex, $cep, $match)) {
			throw new Exception("CEP Inválido!");
		}
		
		$this->cep = str_replace('-', '', $cep);
		
		$this->fetch();
	}
	
	/**
	 * Função mágica que permite que a classe seja transformada em string
	 * Retorno padrão é na forma de um JSON
	 *
	 * @return string JSON relativo ao pacote
	 */
	public function __toString() {
		return $this->toJSON();
	}
	
	/**
	 * Função mágica para dar acesso de leitura aos atributos do cep
	 *
	 * @param string $prop Propriedade sendo acessada
	 * @return mixed Valor atual da propriedade
	 */
	public function __get($prop) {
		if (isset($this->data[$prop])) return $this->data[$prop];
		return null;
	}
	
	/**
	 * Função mágica que permite que o cep seja alterado
	 * Ao alterar o cep, ele recarrega a classe com o novo cep
	 * Qualquer outra alteração nas propriedades do cep é bloqueada
	 *
	 * @param string $prop  Propriedade sendo acessada
	 * @param string $value Novo valor para a propriedade
	 * @return 
	 */
	public function __set($prop, $value) {
		if ($prop == 'cep') 
			$this->__construct($value);
	}
	
	/**
	 * Função que busca dados do cep no webservice
	 */
	private function fetch() {
		$info = file_get_contents($this->resource.$this->cep);
		parse_str(utf8_encode(urldecode($info)), $this->data);
		$this->data['cep'] = $this->cep;
	}
	
	/**
	 * Função que gera o JSON para o cep
	 *
	 * @return string JSON relativo ao cep
	 */
	public function toJSON() {
		return json_encode($this->data);
	}
	
}

