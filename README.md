# PicPay - Adobe Commerce

O módulo PicPay Arranjo Aberto é uma solução de pagamento para lojas virtuais que utilizam o Magento Open Source ou Adobe Commerce. Ele é um facilitador de integração das funcionalidades da PicPay e permite que os lojistas aceitem pagamentos com cartões de crédito e Pix, de forma rápida, segura e confiável.

## Pré-requisitos
O módulo é compatível com a versão 2.3.6+ do Adobe Commerce e está disponível em português e inglês.

- Conta criada no PICPAY. [Crie a sua aqui!](https://ecommerce-gateway.picpay.com/)
- Magento 2.3.6+ instalado
- PHP 7.1+


## Instalação

**Via composer**

```
composer require picpay/ecommerce-integration-magento2

php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy pt_BR en_US
```

**Instalação Manual**

1 - Instale as demais dependências, com os comandos abaixo:

```
composer require firebase/php-jwt:*
composer require bacon/bacon-qr-code:*
```

2 - Faça Download do módulo e coloque na pasta
```
app/code/PicPay/Checkout
```

3 - Execute os comandos de instalação:

```
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy pt_BR en_US
```

## Desinstalar

1 - Remova o módulo, isso dependerá da forma como foi instalado

**Composer**

Rode o comando de remoção via composer:
```
composer remove picpay/ecommerce-integration-magento2
```

**Manual**

Remova a pasta:
```
app/code/PicPay/Checkout
```

2 - Rode os comandos de atualização

```
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy pt_BR en_US
```


Módulo disponível em português e inglês, compatível com a versão 2.4 do Adobe Commerce.
O módulo utiliza a API da PicPay Arranjo Abert para a geração de pagamentos com:
- Cartão de Crédito
- Pix
- Wallet


## Webhooks
Será preciso habilitar e cadastrar os webhook no picpay, para isso acesse o painel do picpay e siga as instruções.  
Link: https://picpay.github.io/picpay-docs-ms-ecommerce-checkout/docs/webhook
