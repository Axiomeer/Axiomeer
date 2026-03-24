<?php

namespace Database\Seeders;

use App\Models\Domain;
use Illuminate\Database\Seeder;

class DomainSeeder extends Seeder
{
    public function run(): void
    {
        $domains = [
            [
                'name' => 'legal',
                'slug' => 'legal',
                'display_name' => 'Legal',
                'icon' => 'bx bx-briefcase',
                'color' => 'primary',
                'citation_format' => 'case_law',
                'system_prompt' => 'You are a legal compliance assistant. Answer questions using only the provided legal documents, contracts, and policies. Cite specific clauses, sections, and paragraphs. Never provide legal advice — only summarize what the documents state. If the answer is not in the documents, say so explicitly.',
            ],
            [
                'name' => 'healthcare',
                'slug' => 'healthcare',
                'display_name' => 'Healthcare',
                'icon' => 'bx bx-plus-medical',
                'color' => 'success',
                'citation_format' => 'clinical',
                'system_prompt' => 'You are a healthcare compliance assistant. Answer questions using only the provided clinical SOPs, guidelines, and policy documents. Cite specific sections and procedure numbers. Never provide medical advice — only summarize what the documents state. If the answer is not in the documents, say so explicitly.',
            ],
            [
                'name' => 'finance',
                'slug' => 'finance',
                'display_name' => 'Finance',
                'icon' => 'bx bx-bar-chart-alt-2',
                'color' => 'warning',
                'citation_format' => 'financial',
                'system_prompt' => 'You are a financial compliance assistant. Answer questions using only the provided financial regulations, reports, and policy documents. Cite specific sections and regulatory references. Never provide financial advice — only summarize what the documents state. If the answer is not in the documents, say so explicitly.',
            ],
        ];

        foreach ($domains as $domain) {
            Domain::updateOrCreate(['slug' => $domain['slug']], $domain);
        }
    }
}
