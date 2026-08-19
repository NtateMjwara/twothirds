-- Migration 008: record what was actually paid
--
-- This exists because of something the portfolio rebuild turned up: the
-- platform has never stored the price an investor paid.
--
-- `shareholdings` records how many shares were settled and when, and
-- `commitments` records how many were requested - but neither keeps the NAV in
-- force at the time. NAV moves whenever an asset is revalued, so today's
-- nav_per_share says what a holding is worth *now* and nothing at all about
-- whether the investor is up or down on it.
--
-- Every profit-and-loss figure on a portfolio page needs a cost basis. Without
-- these two columns there is no honest way to produce one, and the alternative -
-- quietly comparing current NAV against current NAV and always reporting zero -
-- would be worse than showing nothing.
--
-- Rows created before this migration keep NULL. The portfolio page treats NULL
-- as "cost unknown" and says so, rather than assuming a number.

ALTER TABLE shareholdings
    -- The NAV in force when this parcel was settled. Set once, never updated:
    -- the ledger is append-only and this is part of the historical record.
    ADD COLUMN nav_at_settlement DECIMAL(10,4) NULL AFTER shares;

ALTER TABLE commitments
    -- The NAV quoted when the commitment was made. Also what the investor
    -- should be invoiced at, so a revaluation between commitment and settlement
    -- doesn't silently change the price they agreed to.
    ADD COLUMN nav_at_commitment DECIMAL(10,4) NULL AFTER shares_requested;

-- Backfill is deliberately NOT attempted.
--
-- The obvious move is `UPDATE shareholdings SET nav_at_settlement = c.nav_per_share`,
-- and it would be wrong: it would stamp today's NAV onto a parcel settled two
-- years ago and produce a confident, false zero for every historical holding.
-- A visible "cost unknown" is more useful than an invented one.
--
-- If you have the settlement figures elsewhere - invoices, bank records - they
-- can be filled in per row afterwards, and the page will start showing movement
-- for those holdings automatically.
