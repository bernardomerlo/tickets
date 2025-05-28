<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\TicketType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');

            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('closed_at')->nullable();

            $table->string('type');

            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('assigned_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("DROP TYPE IF EXISTS ticket_type CASCADE");
            $values = "'" . implode("','", TicketType::values()) . "'";
            DB::statement("CREATE TYPE ticket_type AS ENUM ({$values})");
            DB::statement("ALTER TABLE tickets ALTER COLUMN type TYPE ticket_type USING type::ticket_type");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
