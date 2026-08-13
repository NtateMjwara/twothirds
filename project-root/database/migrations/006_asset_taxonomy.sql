-- Migration 002: Asset taxonomy (sectors, commercial activities, asset classes)
--
-- Until now `commercial_activities.activity_type` and `assets.make` were free text,
-- and the discovery filters were derived from whatever admins happened to type.
-- That works for ten listings and falls apart at a hundred: "Uber", "uber",
-- "E-hailing" and "Ehailing" all become separate filter pills.
--
-- This migration introduces a controlled vocabulary in three levels:
--   sectors        -> the industry the asset earns in       (Freight & Logistics)
--   activity_types -> the commercial activity inside it     (Cold chain haulage)
--   asset_classes  -> what the asset physically is          (Refrigerated truck)
--
-- The old varchar columns stay. They're now denormalised labels kept in sync on
-- write, so nothing that reads them breaks, and listings created before this
-- migration keep rendering while they're being reclassified.

-- ============================================================
-- Tables
-- ============================================================

CREATE TABLE sectors (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(60) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    tagline VARCHAR(255) NULL,
    icon VARCHAR(60) NOT NULL DEFAULT 'ti-briefcase',   -- Tabler icon class
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE activity_types (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sector_id INT UNSIGNED NOT NULL,
    slug VARCHAR(80) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    description VARCHAR(255) NULL,
    icon VARCHAR(60) NULL,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sector_id) REFERENCES sectors(id),
    INDEX idx_sector (sector_id)
) ENGINE=InnoDB;

CREATE TABLE asset_classes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(60) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    family VARCHAR(60) NOT NULL,        -- grouping for the filter list
    icon VARCHAR(60) NOT NULL DEFAULT 'ti-car',
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- Wire the taxonomy into existing tables
-- Nullable on purpose: pre-migration rows have no classification yet and the
-- discovery page must keep working while an admin backfills them.
-- ============================================================

ALTER TABLE commercial_activities
    ADD COLUMN activity_type_id INT UNSIGNED NULL AFTER company_id,
    ADD CONSTRAINT fk_ca_activity_type FOREIGN KEY (activity_type_id) REFERENCES activity_types(id),
    ADD INDEX idx_activity_type (activity_type_id);

ALTER TABLE assets
    ADD COLUMN asset_class_id INT UNSIGNED NULL AFTER company_id,
    ADD CONSTRAINT fk_asset_class FOREIGN KEY (asset_class_id) REFERENCES asset_classes(id),
    ADD INDEX idx_asset_class (asset_class_id);

-- Discovery sorts and filters on these constantly; without them every filter
-- change is a full scan of companies.
ALTER TABLE companies
    ADD INDEX idx_status_created (status, created_at),
    ADD INDEX idx_status_nav (status, nav_per_share);

-- ============================================================
-- Seed: sectors
-- ============================================================

INSERT INTO sectors (slug, name, tagline, icon, sort_order) VALUES
('mobility',        'Passenger Transport & Mobility', 'Moving people for a fare, on a route or on demand.',        'ti-car',           10),
('logistics',       'Freight & Logistics',            'Moving goods between suppliers, warehouses and doorsteps.',  'ti-truck-delivery', 20),
('construction',    'Construction & Infrastructure',  'Plant and haulage that builds and maintains the built world.','ti-backhoe',      30),
('mining',          'Mining & Resources',             'Fleets working on and around mine sites.',                   'ti-mountain',      40),
('agriculture',     'Agriculture & Agri-processing',  'Equipment that plants, harvests and moves produce.',         'ti-tractor',       50),
('rental',          'Rental & Leasing',               'Assets that earn by being handed to someone else to use.',   'ti-key',           60),
('trade',           'Mobile Trade & Retail',          'The vehicle is the storefront, kitchen or workshop.',        'ti-building-store',70),
('public-services', 'Emergency, Health & Public Services', 'Response, care and safety fleets under contract.',      'ti-ambulance',     80),
('tourism',         'Tourism & Hospitality',          'Vehicles that carry guests, tours and experiences.',         'ti-map-2',         90),
('energy',          'Energy & Utilities',             'Fuel, gas and network maintenance fleets.',                  'ti-gas-station',  100),
('waste',           'Waste & Environmental',          'Collection, recovery and site cleaning services.',           'ti-recycle',      110),
('events',          'Events, Media & Production',     'Short-run logistics for shoots, stages and activations.',    'ti-speakerphone', 120);

-- ============================================================
-- Seed: commercial activities
-- ============================================================

INSERT INTO activity_types (sector_id, slug, name, description, icon, sort_order)
SELECT s.id, t.slug, t.name, t.description, t.icon, t.sort_order FROM sectors s
JOIN (
    -- Passenger Transport & Mobility
    SELECT 'mobility' AS sec, 'e-hailing' AS slug, 'E-hailing' AS name, 'App-dispatched trips on Uber, Bolt or inDrive.' AS description, 'ti-brand-uber' AS icon, 10 AS sort_order UNION ALL
    SELECT 'mobility', 'metered-taxi', 'Metered taxi', 'Ranked and hailed street work on a meter.', 'ti-car-taxi', 20 UNION ALL
    SELECT 'mobility', 'minibus-taxi-route', 'Minibus taxi route', 'Fixed-route commuter work under an association permit.', 'ti-users', 30 UNION ALL
    SELECT 'mobility', 'commuter-bus', 'Commuter bus service', 'Scheduled bus routes carrying fare-paying commuters.', 'ti-bus', 40 UNION ALL
    SELECT 'mobility', 'staff-transport', 'Staff transport', 'Contracted shift transport for a single employer.', 'ti-briefcase', 50 UNION ALL
    SELECT 'mobility', 'scholar-transport', 'Scholar transport', 'Term-time school runs on a subscription basis.', 'ti-school', 60 UNION ALL
    SELECT 'mobility', 'airport-shuttle', 'Airport & hotel shuttle', 'Booked transfers between terminals and venues.', 'ti-plane', 70 UNION ALL
    SELECT 'mobility', 'chauffeur-hire', 'Chauffeur & executive hire', 'Hourly or daily driven hire for corporate clients.', 'ti-steering-wheel', 80 UNION ALL

    -- Freight & Logistics
    SELECT 'logistics', 'long-haul-freight', 'Long-haul freight', 'Line-haul runs between provinces or across borders.', 'ti-truck', 10 UNION ALL
    SELECT 'logistics', 'regional-distribution', 'Regional distribution', 'Depot-to-store delivery on a repeating route.', 'ti-route', 20 UNION ALL
    SELECT 'logistics', 'last-mile-delivery', 'Last-mile delivery', 'Parcels from the local hub to the door.', 'ti-package', 30 UNION ALL
    SELECT 'logistics', 'courier-express', 'Courier & express', 'Same-day and overnight document and parcel work.', 'ti-mail-fast', 40 UNION ALL
    SELECT 'logistics', 'food-delivery', 'Food & grocery delivery', 'On-demand restaurant and retail delivery.', 'ti-tools-kitchen-2', 50 UNION ALL
    SELECT 'logistics', 'cold-chain', 'Cold chain transport', 'Temperature-controlled loads with logged conditions.', 'ti-snowflake', 60 UNION ALL
    SELECT 'logistics', 'bulk-tanker', 'Bulk tanker haulage', 'Liquid and dry bulk under hazardous-goods rules.', 'ti-barrel', 70 UNION ALL
    SELECT 'logistics', 'container-drayage', 'Container drayage', 'Port and rail terminal container moves.', 'ti-box', 80 UNION ALL
    SELECT 'logistics', 'abnormal-load', 'Abnormal load haulage', 'Permitted oversize and overweight transport.', 'ti-ruler-measure', 90 UNION ALL
    SELECT 'logistics', 'livestock-transport', 'Livestock transport', 'Welfare-compliant animal movement.', 'ti-cow', 100 UNION ALL
    SELECT 'logistics', 'removals', 'Furniture removals', 'Household and office relocation contracts.', 'ti-sofa', 110 UNION ALL
    SELECT 'logistics', 'vehicle-recovery', 'Towing & vehicle recovery', 'Breakdown recovery and accident tow-in work.', 'ti-car-crash', 120 UNION ALL

    -- Construction & Infrastructure
    SELECT 'construction', 'plant-hire', 'Plant hire', 'Wet or dry hire of earthmoving equipment by the day.', 'ti-backhoe', 10 UNION ALL
    SELECT 'construction', 'aggregate-haulage', 'Tipper & aggregate haulage', 'Sand, stone and spoil moved by the load.', 'ti-truck-loading', 20 UNION ALL
    SELECT 'construction', 'concrete-supply', 'Concrete supply', 'Ready-mix delivery on a per-cubic-metre rate.', 'ti-building-factory', 30 UNION ALL
    SELECT 'construction', 'crane-lifting', 'Crane & lifting services', 'Mobile lifting with an operator and rigger.', 'ti-crane', 40 UNION ALL
    SELECT 'construction', 'road-maintenance', 'Road maintenance', 'Surfacing, patching and line-marking contracts.', 'ti-road', 50 UNION ALL
    SELECT 'construction', 'site-water-services', 'Site water & dust control', 'Water carting and dust suppression on site.', 'ti-droplet', 60 UNION ALL

    -- Mining & Resources
    SELECT 'mining', 'ore-haulage', 'Ore & coal haulage', 'Pit-to-plant and plant-to-siding tonnage.', 'ti-mountain', 10 UNION ALL
    SELECT 'mining', 'mine-personnel-transport', 'Mine personnel transport', 'Shift-change transport to and around the site.', 'ti-users-group', 20 UNION ALL
    SELECT 'mining', 'underground-utility', 'Underground utility vehicles', 'Flameproof vehicles working below surface.', 'ti-tools', 30 UNION ALL
    SELECT 'mining', 'site-fuel-bowser', 'Site refuelling', 'Mobile bowsers fuelling equipment in the pit.', 'ti-gas-station', 40 UNION ALL
    SELECT 'mining', 'exploration-support', 'Exploration & drill support', 'Logistics support for drilling programmes.', 'ti-drone', 50 UNION ALL

    -- Agriculture & Agri-processing
    SELECT 'agriculture', 'tractor-hire', 'Tractor & implement hire', 'Seasonal hire of tractors with implements.', 'ti-tractor', 10 UNION ALL
    SELECT 'agriculture', 'harvest-contracting', 'Harvest contracting', 'Contract harvesting paid by hectare or tonne.', 'ti-wheat', 20 UNION ALL
    SELECT 'agriculture', 'produce-haulage', 'Farm-to-market haulage', 'Produce moved to markets, packhouses and mills.', 'ti-apple', 30 UNION ALL
    SELECT 'agriculture', 'feed-input-distribution', 'Feed & input distribution', 'Fertiliser, seed and feed delivery to farms.', 'ti-seeding', 40 UNION ALL
    SELECT 'agriculture', 'water-carting', 'Irrigation & water carting', 'Bulk water delivery for stock and irrigation.', 'ti-droplet-filled', 50 UNION ALL

    -- Rental & Leasing
    SELECT 'rental', 'car-rental', 'Daily car rental', 'Short-term self-drive rental off a counter or app.', 'ti-key', 10 UNION ALL
    SELECT 'rental', 'vehicle-subscription', 'Vehicle subscription', 'Month-to-month all-inclusive vehicle access.', 'ti-calendar-repeat', 20 UNION ALL
    SELECT 'rental', 'fleet-leasing', 'Corporate fleet leasing', 'Multi-year full-maintenance leases to a business.', 'ti-building-skyscraper', 30 UNION ALL
    SELECT 'rental', 'bakkie-van-rental', 'Bakkie & van rental', 'Self-drive load-carrying rental by the day.', 'ti-car-suv', 40 UNION ALL
    SELECT 'rental', 'trailer-rental', 'Trailer & equipment rental', 'Dry hire of trailers and site equipment.', 'ti-trailer', 50 UNION ALL
    SELECT 'rental', 'rent-to-own', 'Driver rent-to-own', 'Structured rental converting to driver ownership.', 'ti-progress-check', 60 UNION ALL

    -- Mobile Trade & Retail
    SELECT 'trade', 'food-truck', 'Mobile food & beverage', 'Trading food and drink from a fitted vehicle.', 'ti-tools-kitchen-3', 10 UNION ALL
    SELECT 'trade', 'mobile-retail', 'Mobile retail & vending', 'Stock sold direct from a route vehicle.', 'ti-building-store', 20 UNION ALL
    SELECT 'trade', 'direct-store-delivery', 'Direct store delivery', 'Van sales replenishing spaza and forecourt stock.', 'ti-shopping-cart', 30 UNION ALL
    SELECT 'trade', 'mobile-car-wash', 'Mobile wash & detailing', 'On-site valeting for fleets and households.', 'ti-bubble', 40 UNION ALL
    SELECT 'trade', 'mobile-workshop', 'Mobile workshop & roadside', 'Fitted service vehicles doing on-site repairs.', 'ti-tool', 50 UNION ALL

    -- Emergency, Health & Public Services
    SELECT 'public-services', 'emergency-medical', 'Emergency medical services', 'Licensed ambulance response under contract.', 'ti-ambulance', 10 UNION ALL
    SELECT 'public-services', 'patient-transport', 'Patient transport', 'Non-emergency scheduled patient movement.', 'ti-wheelchair', 20 UNION ALL
    SELECT 'public-services', 'mobile-clinic', 'Mobile clinic', 'Fitted clinics running outreach schedules.', 'ti-stethoscope', 30 UNION ALL
    SELECT 'public-services', 'medical-courier', 'Medical & lab courier', 'Time-critical specimen and pharmaceutical runs.', 'ti-vaccine-bottle', 40 UNION ALL
    SELECT 'public-services', 'security-response', 'Security patrol & response', 'Armed response and patrol route contracts.', 'ti-shield-check', 50 UNION ALL
    SELECT 'public-services', 'cash-in-transit', 'Cash in transit', 'Armoured collection and delivery of valuables.', 'ti-lock', 60 UNION ALL
    SELECT 'public-services', 'municipal-services', 'Municipal services', 'Contracted work for a municipality or agency.', 'ti-building-community', 70 UNION ALL

    -- Tourism & Hospitality
    SELECT 'tourism', 'game-drive', 'Game drive & safari', 'Guided open-vehicle drives at a lodge or reserve.', 'ti-binoculars', 10 UNION ALL
    SELECT 'tourism', 'tour-coach', 'Tour coach operation', 'Multi-day coach itineraries for tour operators.', 'ti-bus-stop', 20 UNION ALL
    SELECT 'tourism', 'overland-adventure', 'Overland & adventure', 'Expedition vehicles on long-distance routes.', 'ti-tent', 30 UNION ALL
    SELECT 'tourism', 'lodge-logistics', 'Lodge & venue logistics', 'Supply and guest runs for remote properties.', 'ti-building-castle', 40 UNION ALL

    -- Energy & Utilities
    SELECT 'energy', 'fuel-distribution', 'Fuel distribution', 'Depot-to-forecourt petroleum delivery.', 'ti-gas-station', 10 UNION ALL
    SELECT 'energy', 'lpg-distribution', 'LPG & gas distribution', 'Cylinder and bulk gas delivery routes.', 'ti-flame', 20 UNION ALL
    SELECT 'energy', 'utility-maintenance', 'Utility maintenance fleet', 'Cherry pickers and service units on network work.', 'ti-bolt', 30 UNION ALL
    SELECT 'energy', 'telecoms-servicing', 'Telecoms site servicing', 'Tower and base-station maintenance runs.', 'ti-antenna', 40 UNION ALL
    SELECT 'energy', 'solar-installation', 'Solar & backup installation', 'Installation crews and equipment transport.', 'ti-solar-panel', 50 UNION ALL

    -- Waste & Environmental
    SELECT 'waste', 'waste-collection', 'Waste collection', 'Scheduled skip and bin collection rounds.', 'ti-trash', 10 UNION ALL
    SELECT 'waste', 'recycling-haulage', 'Recycling haulage', 'Recovered material moved to buy-back and mills.', 'ti-recycle', 20 UNION ALL
    SELECT 'waste', 'hazardous-waste', 'Hazardous waste transport', 'Licensed movement of regulated waste streams.', 'ti-biohazard', 30 UNION ALL
    SELECT 'waste', 'sanitation-services', 'Sanitation & honeysucker', 'Vacuum tanker servicing of sanitation systems.', 'ti-droplet-off', 40 UNION ALL

    -- Events, Media & Production
    SELECT 'events', 'event-logistics', 'Event logistics', 'Staging, seating and equipment moved to venue.', 'ti-tent', 10 UNION ALL
    SELECT 'events', 'mobile-production', 'Mobile production unit', 'Fitted broadcast and production vehicles on hire.', 'ti-video', 20 UNION ALL
    SELECT 'events', 'film-unit-transport', 'Film unit transport', 'Crew, camera and grip transport for productions.', 'ti-movie', 30 UNION ALL
    SELECT 'events', 'brand-activation', 'Brand activation vehicles', 'Branded roadshow vehicles on campaign hire.', 'ti-speakerphone', 40
) AS t ON t.sec = s.slug;

-- ============================================================
-- Seed: asset classes
-- ============================================================

INSERT INTO asset_classes (slug, name, family, icon, sort_order) VALUES
('hatchback',          'Hatchback',                    'Passenger',    'ti-car',            10),
('sedan',              'Sedan',                        'Passenger',    'ti-car',            20),
('suv',                'SUV & crossover',              'Passenger',    'ti-car-suv',        30),
('mpv',                'MPV & people carrier',         'Passenger',    'ti-car-suv',        40),
('minibus',            'Minibus (8-16 seat)',          'Passenger',    'ti-bus',            50),
('midibus',            'Midibus (17-35 seat)',         'Passenger',    'ti-bus',            60),
('coach',              'Bus & coach (35+ seat)',       'Passenger',    'ti-bus-stop',       70),
('motorcycle',         'Motorcycle & scooter',         'Two-wheel',    'ti-motorbike',      80),
('three-wheeler',      'Three-wheeler',                'Two-wheel',    'ti-motorbike',      90),
('bakkie-single',      'Bakkie - single cab',          'Light commercial', 'ti-car-suv',   100),
('bakkie-double',      'Bakkie - double cab',          'Light commercial', 'ti-car-suv',   110),
('panel-van',          'Panel van',                    'Light commercial', 'ti-truck',     120),
('dropside-ldv',       'Dropside LDV',                 'Light commercial', 'ti-truck',     130),
('rigid-truck-light',  'Rigid truck - light (3-8t)',   'Trucks',       'ti-truck',         140),
('rigid-truck-medium', 'Rigid truck - medium (8-16t)', 'Trucks',       'ti-truck',         150),
('rigid-truck-heavy',  'Rigid truck - heavy (16t+)',   'Trucks',       'ti-truck',         160),
('truck-tractor',      'Truck tractor (horse)',        'Trucks',       'ti-tir',           170),
('tipper',             'Tipper',                       'Trucks',       'ti-truck-loading', 180),
('mixer',              'Concrete mixer',               'Trucks',       'ti-building-factory', 190),
('tanker',             'Tanker',                       'Trucks',       'ti-barrel',        200),
('refrigerated',       'Refrigerated body',            'Trucks',       'ti-snowflake',     210),
('tow-truck',          'Tow & recovery truck',         'Trucks',       'ti-car-crash',     220),
('trailer-flatdeck',   'Trailer - flatdeck',           'Trailers',     'ti-trailer',       230),
('trailer-tautliner',  'Trailer - tautliner',          'Trailers',     'ti-trailer',       240),
('trailer-tipper',     'Trailer - tipper',             'Trailers',     'ti-trailer',       250),
('trailer-tanker',     'Trailer - tanker',             'Trailers',     'ti-trailer',       260),
('trailer-refrigerated','Trailer - refrigerated',      'Trailers',     'ti-trailer',       270),
('tlb',                'TLB / backhoe loader',         'Plant',        'ti-backhoe',       280),
('excavator',          'Excavator',                    'Plant',        'ti-backhoe',       290),
('front-end-loader',   'Front-end loader',             'Plant',        'ti-backhoe',       300),
('grader',             'Grader',                       'Plant',        'ti-backhoe',       310),
('roller',             'Roller & compactor',           'Plant',        'ti-backhoe',       320),
('forklift',           'Forklift',                     'Plant',        'ti-forklift',      330),
('telehandler',        'Telehandler',                  'Plant',        'ti-forklift',      340),
('mobile-crane',       'Mobile crane',                 'Plant',        'ti-crane',         350),
('cherry-picker',      'Cherry picker',                'Plant',        'ti-crane',         360),
('tractor',            'Agricultural tractor',         'Agricultural', 'ti-tractor',       370),
('harvester',          'Harvester',                    'Agricultural', 'ti-wheat',         380),
('farm-implement',     'Farm implement',               'Agricultural', 'ti-seeding',       390),
('ambulance',          'Ambulance',                    'Specialised',  'ti-ambulance',     400),
('armoured',           'Armoured vehicle',             'Specialised',  'ti-shield-check',  410),
('mobile-clinic-unit', 'Mobile clinic unit',           'Specialised',  'ti-stethoscope',   420),
('food-truck-unit',    'Food truck / kitchen unit',    'Specialised',  'ti-tools-kitchen-3', 430),
('refuse-compactor',   'Refuse compactor',             'Specialised',  'ti-trash',         440),
('vacuum-tanker',      'Vacuum tanker',                'Specialised',  'ti-droplet-off',   450),
('game-viewer',        'Open game viewer',             'Specialised',  'ti-binoculars',    460),
('production-unit',    'Mobile production unit',       'Specialised',  'ti-video',         470);

-- ============================================================
-- Backfill
-- Exact, case-insensitive name matches only. Anything an admin typed that
-- doesn't line up stays NULL and shows as "Unclassified" in the admin list -
-- guessing here would put listings in the wrong sector silently.
-- ============================================================

UPDATE commercial_activities ca
JOIN activity_types att ON LOWER(att.name) = LOWER(TRIM(ca.activity_type))
SET ca.activity_type_id = att.id
WHERE ca.activity_type_id IS NULL;

-- Common shorthand admins already used.
UPDATE commercial_activities ca
JOIN activity_types att ON att.slug = 'e-hailing'
SET ca.activity_type_id = att.id
WHERE ca.activity_type_id IS NULL
  AND LOWER(TRIM(ca.activity_type)) IN ('uber', 'bolt', 'indrive', 'ehailing', 'e hailing', 'ride hailing');

UPDATE assets a
JOIN asset_classes ac ON LOWER(ac.name) = LOWER(TRIM(a.make))
SET a.asset_class_id = ac.id
WHERE a.asset_class_id IS NULL;
