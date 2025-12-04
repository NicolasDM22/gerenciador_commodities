
CREATE TABLE `gerenciador_commodities`.`previsoes` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `commodity_nome` VARCHAR(45) NULL,
  `data_previsao` DATETIME NULL,
  `acao` VARCHAR(45) NULL,
  PRIMARY KEY (`id`));

