-- Base dédiée à la suite de tests Pest.
-- Exécuté une seule fois, à l'initialisation du volume PostgreSQL.
--
-- Les tests de la V1 (invariants comptables, test G1 de cohérence globale)
-- doivent tourner sur PostgreSQL et non sur SQLite : contraintes CHECK,
-- types bigint et comportement transactionnel doivent être identiques
-- à ceux de la production.

CREATE DATABASE bayan_testing;
