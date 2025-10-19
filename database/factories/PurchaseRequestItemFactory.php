<?php

namespace Database\Factories;

use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PurchaseRequestItem>
 */
class PurchaseRequestItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $itemCategories = [
            'office_supplies',
            'equipment',
            'materials',
            'services',
            'infrastructure',
            'ict_equipment',
            'furniture',
            'consumables',
            'other'
        ];

        $itemStatuses = ['pending', 'approved', 'rejected', 'modified', 'cancelled'];

        $unitsOfMeasure = [
            'pcs',
            'kg',
            'meters',
            'liters',
            'boxes',
            'reams',
            'units',
            'sets',
            'pairs',
            'bottles',
            'rolls',
            'sheets',
            'hours',
            'days'
        ];

        // Generate realistic items based on category
        $itemsData = $this->getItemsDataByCategory();
        $selectedCategory = $this->faker->randomElement($itemCategories);
        $categoryItems = $itemsData[$selectedCategory] ?? $itemsData['other'];
        $selectedItem = $this->faker->randomElement($categoryItems);

        $quantity = $this->faker->numberBetween(1, 20); // Increased max quantity to keep totals reasonable
        $unitCost = $this->faker->randomFloat(2, 50, 2500); // Reduced max unit cost
        $totalCost = $quantity * $unitCost;

        $itemStatus = $this->faker->randomElement($itemStatuses);
        $isApproved = in_array($itemStatus, ['approved', 'modified']);

        return [
            'purchase_request_id' => PurchaseRequest::factory(),
            'item_code' => $this->faker->optional(0.6)->regexify('[A-Z]{2}[0-9]{6}'),
            'item_name' => $selectedItem['name'],
            'detailed_specifications' => $selectedItem['specifications'],
            'unit_of_measure' => $selectedItem['unit'] ?? $this->faker->randomElement($unitsOfMeasure),
            'quantity_requested' => $quantity,
            'estimated_unit_cost' => $unitCost,
            'estimated_total_cost' => $totalCost,
            'item_category' => $selectedCategory,
            'special_requirements' => $this->faker->optional(0.4)->randomElement([
                'Delivery within office hours only',
                'Installation required',
                'Training included',
                'Warranty required',
                'Brand specification required',
                'Technical support included',
                'Assembly required',
                'Testing and commissioning required'
            ]),
            'needed_by_date' => $this->faker->optional(0.3)->dateTimeBetween('now', '+2 months'),
            'is_available_locally' => $this->faker->boolean(80),
            'budget_line_item' => $this->faker->optional(0.7)->regexify('[A-Z]{3}-[0-9]{4}'),
            'approved_budget' => $isApproved ? $totalCost * $this->faker->randomFloat(2, 0.8, 1.2) : null,
            'item_status' => $itemStatus,
            'rejection_reason' => $itemStatus === 'rejected'
                ? $this->faker->randomElement([
                    'Budget constraints',
                    'Item not essential',
                    'Alternative solution available',
                    'Specifications unclear',
                    'Procurement method not appropriate'
                ]) : null,
            'modification_notes' => $itemStatus === 'modified'
                ? $this->faker->sentence() : null,
            'awarded_unit_price' => $isApproved && $this->faker->boolean(60)
                ? $unitCost * $this->faker->randomFloat(2, 0.7, 1.1) : null,
            'awarded_total_price' => $isApproved && $this->faker->boolean(60)
                ? $totalCost * $this->faker->randomFloat(2, 0.7, 1.1) : null,
            'awarded_supplier_id' => $isApproved && $this->faker->boolean(60)
                ? (\App\Models\Supplier::inRandomOrder()->first()?->id ?? \App\Models\Supplier::factory()) : null,
        ];
    }

    /**
     * Get realistic items data organized by category.
     */
    private function getItemsDataByCategory(): array
    {
        return [
            'office_supplies' => [
                ['name' => 'Bond Paper A4 80gsm', 'specifications' => 'White bond paper, A4 size, 80gsm weight, 500 sheets per ream', 'unit' => 'reams'],
                ['name' => 'Ballpoint Pen Blue', 'specifications' => 'Blue ink ballpoint pen, medium tip, retractable', 'unit' => 'pcs'],
                ['name' => 'Manila Folder Legal Size', 'specifications' => 'Manila folder, legal size, 230gsm', 'unit' => 'pcs'],
                ['name' => 'Stapler Heavy Duty', 'specifications' => 'Heavy duty stapler, 50-sheet capacity, metal construction', 'unit' => 'pcs'],
                ['name' => 'Correction Tape', 'specifications' => '5mm width correction tape, 8-meter length', 'unit' => 'pcs'],
                ['name' => 'Ring Binder A4', 'specifications' => 'A4 ring binder, 2-inch capacity, vinyl cover', 'unit' => 'pcs'],
                ['name' => 'Permanent Marker Black', 'specifications' => 'Black permanent marker, chisel tip, alcohol-based ink', 'unit' => 'pcs'],
                ['name' => 'Expanding File Folder', 'specifications' => '13-pocket expanding file folder, legal size', 'unit' => 'pcs'],
            ],
            'equipment' => [
                ['name' => 'Desktop Computer', 'specifications' => 'Intel i5 processor, 8GB RAM, 256GB SSD, Windows 11 Pro', 'unit' => 'units'],
                ['name' => 'Laser Printer Monochrome', 'specifications' => 'Monochrome laser printer, 30ppm, duplex printing, network ready', 'unit' => 'units'],
                ['name' => 'Air Conditioning Unit', 'specifications' => '1.5HP split-type air conditioner, inverter technology, energy efficient', 'unit' => 'units'],
                ['name' => 'Digital Camera', 'specifications' => '24MP DSLR camera with 18-55mm lens, full HD video recording', 'unit' => 'units'],
                ['name' => 'Projector LCD', 'specifications' => '3000 lumens LCD projector, 1024x768 resolution, HDMI connectivity', 'unit' => 'units'],
                ['name' => 'UPS 1000VA', 'specifications' => '1000VA uninterruptible power supply, line interactive, LCD display', 'unit' => 'units'],
                ['name' => 'Network Switch 24-port', 'specifications' => '24-port gigabit network switch, managed, rack mountable', 'unit' => 'units'],
                ['name' => 'Scanner Flatbed', 'specifications' => 'A4 flatbed scanner, 1200x1200 dpi, USB connectivity', 'unit' => 'units'],
            ],
            'furniture' => [
                ['name' => 'Office Chair Executive', 'specifications' => 'Executive office chair, leather upholstery, ergonomic design, adjustable height', 'unit' => 'pcs'],
                ['name' => 'Office Table 4ft', 'specifications' => '4ft office table, laminated wood top, metal legs, with drawer', 'unit' => 'pcs'],
                ['name' => 'Filing Cabinet 4-drawer', 'specifications' => '4-drawer filing cabinet, legal size, lockable, metal construction', 'unit' => 'pcs'],
                ['name' => 'Conference Table 8-seater', 'specifications' => '8-seater conference table, oval shape, laminated wood finish', 'unit' => 'pcs'],
                ['name' => 'Bookshelf 5-tier', 'specifications' => '5-tier bookshelf, wooden construction, 180cm height', 'unit' => 'pcs'],
                ['name' => 'Reception Sofa 3-seater', 'specifications' => '3-seater reception sofa, fabric upholstery, modern design', 'unit' => 'pcs'],
                ['name' => 'Office Partition', 'specifications' => 'Office partition panel, 150cm height, fabric covered', 'unit' => 'pcs'],
                ['name' => 'Visitor Chair', 'specifications' => 'Visitor chair, plastic seat and back, metal legs, stackable', 'unit' => 'pcs'],
            ],
            'ict_equipment' => [
                ['name' => 'Laptop Computer', 'specifications' => 'Intel i7 processor, 16GB RAM, 512GB SSD, 14-inch display, Windows 11 Pro', 'unit' => 'units'],
                ['name' => 'Network Router', 'specifications' => 'Wireless router, dual-band AC1200, 4 ethernet ports, firewall', 'unit' => 'units'],
                ['name' => 'External Hard Drive 2TB', 'specifications' => '2TB external hard drive, USB 3.0, portable, backup software included', 'unit' => 'units'],
                ['name' => 'Webcam HD', 'specifications' => 'HD webcam, 1080p resolution, built-in microphone, USB connection', 'unit' => 'units'],
                ['name' => 'Wireless Mouse', 'specifications' => 'Wireless optical mouse, 2.4GHz connection, 1600 DPI', 'unit' => 'pcs'],
                ['name' => 'Keyboard Wireless', 'specifications' => 'Wireless keyboard, 2.4GHz connection, multimedia keys', 'unit' => 'pcs'],
                ['name' => 'Monitor 24-inch', 'specifications' => '24-inch LED monitor, 1920x1080 resolution, HDMI and VGA inputs', 'unit' => 'units'],
                ['name' => 'USB Flash Drive 32GB', 'specifications' => '32GB USB 3.0 flash drive, retractable design, encryption software', 'unit' => 'pcs'],
            ],
            'materials' => [
                ['name' => 'Cement Portland', 'specifications' => 'Portland cement, 40kg bag, Type I, ASTM standard', 'unit' => 'bags'],
                ['name' => 'Steel Rebar 12mm', 'specifications' => '12mm steel rebar, 6-meter length, Grade 60', 'unit' => 'pcs'],
                ['name' => 'Paint Latex White', 'specifications' => 'White latex paint, 4-liter container, semi-gloss finish', 'unit' => 'gallons'],
                ['name' => 'Electrical Wire 12AWG', 'specifications' => '12AWG electrical wire, THHN insulation, copper conductor', 'unit' => 'meters'],
                ['name' => 'PVC Pipe 4-inch', 'specifications' => '4-inch PVC pipe, 6-meter length, pressure rated', 'unit' => 'pcs'],
                ['name' => 'Tile Ceramic 30x30cm', 'specifications' => '30x30cm ceramic floor tile, non-slip surface, Grade A', 'unit' => 'pcs'],
                ['name' => 'Plywood 1/2 inch', 'specifications' => '1/2 inch marine plywood, 4x8 feet, Grade A', 'unit' => 'sheets'],
                ['name' => 'Roofing Sheet Galvanized', 'specifications' => 'Galvanized iron roofing sheet, 26 gauge, 8-feet length', 'unit' => 'sheets'],
            ],
            'consumables' => [
                ['name' => 'Toner Cartridge Black', 'specifications' => 'Black toner cartridge, compatible with HP LaserJet series, high yield', 'unit' => 'pcs'],
                ['name' => 'Ink Cartridge Color Set', 'specifications' => 'Color ink cartridge set (CMY), compatible with Canon printers', 'unit' => 'sets'],
                ['name' => 'Cleaning Supplies Kit', 'specifications' => 'Office cleaning supplies kit: disinfectant, glass cleaner, wipes', 'unit' => 'kits'],
                ['name' => 'Coffee Beans Premium', 'specifications' => 'Premium coffee beans, arabica blend, 1kg pack', 'unit' => 'kg'],
                ['name' => 'Tissue Paper Facial', 'specifications' => 'Facial tissue paper, 2-ply, 200 sheets per box', 'unit' => 'boxes'],
                ['name' => 'Hand Sanitizer 500ml', 'specifications' => '500ml hand sanitizer, 70% alcohol content, pump dispenser', 'unit' => 'bottles'],
                ['name' => 'Battery AA Alkaline', 'specifications' => 'AA alkaline batteries, 1.5V, long-lasting, 4-pack', 'unit' => 'packs'],
                ['name' => 'Trash Bags Heavy Duty', 'specifications' => 'Heavy duty trash bags, 30-gallon capacity, 50 pieces per roll', 'unit' => 'rolls'],
            ],
            'services' => [
                ['name' => 'IT Support Services', 'specifications' => 'Monthly IT support and maintenance services, 8 hours coverage', 'unit' => 'months'],
                ['name' => 'Cleaning Services', 'specifications' => 'Daily office cleaning services including floors, restrooms, and common areas', 'unit' => 'months'],
                ['name' => 'Security Guard Services', 'specifications' => '24/7 security guard services, licensed and bonded personnel', 'unit' => 'months'],
                ['name' => 'Pest Control Services', 'specifications' => 'Monthly pest control and termite treatment services', 'unit' => 'months'],
                ['name' => 'Equipment Maintenance', 'specifications' => 'Quarterly maintenance service for office equipment and appliances', 'unit' => 'quarters'],
                ['name' => 'Landscaping Services', 'specifications' => 'Weekly landscaping and gardening services for office premises', 'unit' => 'months'],
                ['name' => 'Vehicle Maintenance', 'specifications' => 'Comprehensive vehicle maintenance service including oil change and inspection', 'unit' => 'services'],
                ['name' => 'Training Services', 'specifications' => 'Professional development training services, 8-hour workshop', 'unit' => 'sessions'],
            ],
            'infrastructure' => [
                ['name' => 'CCTV Camera System', 'specifications' => '16-channel CCTV system with 8 IP cameras, night vision, remote monitoring', 'unit' => 'systems'],
                ['name' => 'Fire Alarm System', 'specifications' => 'Addressable fire alarm system, smoke detectors, control panel, sirens', 'unit' => 'systems'],
                ['name' => 'Access Control System', 'specifications' => 'Biometric access control system, 4 doors, software included', 'unit' => 'systems'],
                ['name' => 'Solar Panel System 5kW', 'specifications' => '5kW solar panel system, grid-tied, inverter and mounting included', 'unit' => 'systems'],
                ['name' => 'Generator Set 20kVA', 'specifications' => '20kVA diesel generator set, automatic start, weather enclosure', 'unit' => 'units'],
                ['name' => 'Water Tank 1000L', 'specifications' => '1000-liter polyethylene water tank, UV resistant, with fittings', 'unit' => 'units'],
                ['name' => 'Elevator Modernization', 'specifications' => 'Complete elevator modernization including controller, motors, and fixtures', 'unit' => 'units'],
                ['name' => 'Parking Lot Construction', 'specifications' => 'Concrete parking lot construction, 20 parking spaces, line marking', 'unit' => 'lots'],
            ],
            'other' => [
                ['name' => 'Miscellaneous Item', 'specifications' => 'General purpose item with basic specifications', 'unit' => 'pcs'],
                ['name' => 'Special Equipment', 'specifications' => 'Specialized equipment with custom specifications', 'unit' => 'units'],
                ['name' => 'Custom Service', 'specifications' => 'Customized service based on specific requirements', 'unit' => 'services'],
                ['name' => 'Bulk Supplies', 'specifications' => 'Bulk supplies package with mixed items', 'unit' => 'lots'],
            ],
        ];
    }

    /**
     * Indicate that the item is approved.
     */
    public function approved(): static
    {
        return $this->state(fn(array $attributes) => [
            'item_status' => 'approved',
            'approved_budget' => $attributes['estimated_total_cost'] * $this->faker->randomFloat(2, 0.9, 1.1),
            'awarded_unit_price' => $attributes['estimated_unit_cost'] * $this->faker->randomFloat(2, 0.8, 1.0),
            'awarded_total_price' => $attributes['estimated_total_cost'] * $this->faker->randomFloat(2, 0.8, 1.0),
        ]);
    }

    /**
     * Indicate that the item is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn(array $attributes) => [
            'item_status' => 'rejected',
            'rejection_reason' => $this->faker->randomElement([
                'Budget constraints',
                'Item not essential',
                'Alternative solution available',
                'Specifications unclear',
                'Procurement method not appropriate'
            ]),
            'approved_budget' => null,
            'awarded_unit_price' => null,
            'awarded_total_price' => null,
            'awarded_supplier_id' => null,
        ]);
    }

    /**
     * Indicate that the item is high value (expensive).
     */
    public function highValue(): static
    {
        $unitCost = $this->faker->randomFloat(2, 5000, 50000);
        $quantity = $this->faker->numberBetween(1, 10);

        return $this->state(fn(array $attributes) => [
            'estimated_unit_cost' => $unitCost,
            'quantity_requested' => $quantity,
            'estimated_total_cost' => $unitCost * $quantity,
        ]);
    }

    /**
     * Indicate that the item is office supplies.
     */
    public function officeSupplies(): static
    {
        $items = $this->getItemsDataByCategory()['office_supplies'];
        $selectedItem = $this->faker->randomElement($items);

        return $this->state(fn(array $attributes) => [
            'item_category' => 'office_supplies',
            'item_name' => $selectedItem['name'],
            'detailed_specifications' => $selectedItem['specifications'],
            'unit_of_measure' => $selectedItem['unit'],
            'estimated_unit_cost' => $this->faker->randomFloat(2, 10, 500),
        ]);
    }

    /**
     * Indicate that the item is equipment.
     */
    public function equipment(): static
    {
        $items = $this->getItemsDataByCategory()['equipment'];
        $selectedItem = $this->faker->randomElement($items);

        return $this->state(fn(array $attributes) => [
            'item_category' => 'equipment',
            'item_name' => $selectedItem['name'],
            'detailed_specifications' => $selectedItem['specifications'],
            'unit_of_measure' => $selectedItem['unit'],
            'estimated_unit_cost' => $this->faker->randomFloat(2, 500, 2500), // Reduced for small value
        ]);
    }
}
