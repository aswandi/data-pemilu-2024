# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel 11/12 web application for displaying Indonesian Election (PEMILU) 2024 data. The application uses:
- Laravel 11/12 (latest version)
- Inertia.js for SPA-like experience
- Vue.js for frontend
- Tailwind CSS for styling
- VueUse Motion for animations
- MySQL database

**Important**: This is Laravel 11/12 - do NOT create `Kernel.php` as it no longer exists in these versions.

## Database Configuration

- **Database**: `050_vscode_clacode_laravel11_pemilu2024`
- **User**: `root2`
- **Password**: `kansas2`
- **Host**: MySQL (likely localhost in Laragon environment)

**Note**: All tables and data already exist in the database. Do NOT create new tables.

## Application Requirements

- **Language**: All content and UI must be in Indonesian (Bahasa Indonesia)
- **Name**: "DATABASE PEMILU 2024"
- **Design**: Modern, responsive design with animations on cards, tables, and sections
- **Tech Stack**: Laravel + Inertia + Vue + Tailwind + VueUse Motion

## Database Schema Structure

### Regional Data Tables (Primary Focus)
The main regional hierarchy tables to work with:

1. **`pdpr_wil_pro`** - Provinces (Provinsi)
   - Contains: id, nama, pro_kode, etc.
   - Primary table for initial province listing page

2. **`pdpr_wil_dapil`** - Electoral Districts (Daerah Pemilihan)
   - Contains: id, nama, pro_id, dapil_kode, etc.

3. **`pdpr_wil_kab`** - Regencies/Cities (Kabupaten/Kota)
   - Contains: id, nama, pro_id, kab_kode, etc.

4. **`pdpr_wil_kec`** - Districts (Kecamatan)
   - Contains: id, nama, pro_id, kab_id, kec_kode, etc.

5. **`pdpr_wil_kel`** - Villages (Kelurahan/Desa)
   - Contains: id, nama, pro_id, kab_id, kec_id, kel_kode, etc.

6. **`pdpr_wil_tps`** - Polling Stations (Tempat Pemungutan Suara)
   - Contains: id, nama, pro_id, kab_id, kec_id, kel_id, tps_kode, etc.

### Candidate Tables
- **`dpd_caleg`** - Regional Representative Council candidates
- **`dpr_ri_caleg`** - House of Representatives candidates
- **`dprd_kab_caleg`** - Regional House of Representatives (Regency) candidates
- **`dprd_pro_caleg`** - Regional House of Representatives (Province) candidates

### Electoral District Tables
- **`dpr_ri_dapil`** - DPR RI electoral districts
- **`dprd_kab_dapil`** - DPRD Kabupaten electoral districts
- **`dprd_pro_dapil`** - DPRD Provinsi electoral districts

## Initial Development Task

**First Page**: Provincial data page showing:
- Table columns: NO, NAMA PROVINSI, JUMLAH DAPIL, JUMLAH KABUPATEN/KOTA, JUMLAH TPS, JUMLAH DPT
- Data source: `pdpr_wil_pro` table
- Related data can be calculated from other `pdpr_wil_*` tables

## Project State

Currently contains:
- `application-concept.md` - Project requirements and specifications
- `database-schema.sql` - Complete database schema with all tables and data
- This directory is ready for Laravel project initialization

## Development Notes

- Use Laravel 11/12 best practices
- Implement responsive design with Tailwind CSS
- Add smooth animations using VueUse Motion
- Follow Indonesian naming conventions and language
- Ensure all UI text is in Bahasa Indonesia
- The existing database contains comprehensive election data that should not be modified