<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ParasiteSpeciesFactory extends Factory
{
    protected $speciesData = [
        // Ticks (Ixodida)
        ['name_scientific' => 'Amblyomma hebraeum', 'genus' => 'Amblyomma', 'family' => 'Ixodidae', 'order' => 'Ixodida', 'class' => 'Arachnida', 'phylum' => 'Arthropoda'],
        ['name_scientific' => 'Amblyomma variegatum', 'genus' => 'Amblyomma', 'family' => 'Ixodidae', 'order' => 'Ixodida', 'class' => 'Arachnida', 'phylum' => 'Arthropoda'],
        ['name_scientific' => 'Rhipicephalus decoloratus', 'genus' => 'Rhipicephalus', 'family' => 'Ixodidae', 'order' => 'Ixodida', 'class' => 'Arachnida', 'phylum' => 'Arthropoda'],
        ['name_scientific' => 'Rhipicephalus microplus', 'genus' => 'Rhipicephalus', 'family' => 'Ixodidae', 'order' => 'Ixodida', 'class' => 'Arachnida', 'phylum' => 'Arthropoda'],
        ['name_scientific' => 'Rhipicephalus sanguineus', 'genus' => 'Rhipicephalus', 'family' => 'Ixodidae', 'order' => 'Ixodida', 'class' => 'Arachnida', 'phylum' => 'Arthropoda'],
        ['name_scientific' => 'Ixodes ricinus', 'genus' => 'Ixodes', 'family' => 'Ixodidae', 'order' => 'Ixodida', 'class' => 'Arachnida', 'phylum' => 'Arthropoda'],
        ['name_scientific' => 'Ixodes scapularis', 'genus' => 'Ixodes', 'family' => 'Ixodidae', 'order' => 'Ixodida', 'class' => 'Arachnida', 'phylum' => 'Arthropoda'],
        ['name_scientific' => 'Haemaphysalis leachi', 'genus' => 'Haemaphysalis', 'family' => 'Ixodidae', 'order' => 'Ixodida', 'class' => 'Arachnida', 'phylum' => 'Arthropoda'],
        ['name_scientific' => 'Dermacentor reticulatus', 'genus' => 'Dermacentor', 'family' => 'Ixodidae', 'order' => 'Ixodida', 'class' => 'Arachnida', 'phylum' => 'Arthropoda'],
        ['name_scientific' => 'Hyalomma marginatum', 'genus' => 'Hyalomma', 'family' => 'Ixodidae', 'order' => 'Ixodida', 'class' => 'Arachnida', 'phylum' => 'Arthropoda'],

        // Mites (Acari)
        ['name_scientific' => 'Sarcoptes scabiei', 'genus' => 'Sarcoptes', 'family' => 'Sarcoptidae', 'order' => 'Sarcoptiformes', 'class' => 'Arachnida', 'phylum' => 'Arthropoda'],
        ['name_scientific' => 'Demodex folliculorum', 'genus' => 'Demodex', 'family' => 'Demodicidae', 'order' => 'Trombidiformes', 'class' => 'Arachnida', 'phylum' => 'Arthropoda'],
        ['name_scientific' => 'Demodex brevis', 'genus' => 'Demodex', 'family' => 'Demodicidae', 'order' => 'Trombidiformes', 'class' => 'Arachnida', 'phylum' => 'Arthropoda'],
        ['name_scientific' => 'Psoroptes ovis', 'genus' => 'Psoroptes', 'family' => 'Psoroptidae', 'order' => 'Sarcoptiformes', 'class' => 'Arachnida', 'phylum' => 'Arthropoda'],
        ['name_scientific' => 'Chorioptes bovis', 'genus' => 'Chorioptes', 'family' => 'Psoroptidae', 'order' => 'Sarcoptiformes', 'class' => 'Arachnida', 'phylum' => 'Arthropoda'],

        // Roundworms (Nematoda)
        ['name_scientific' => 'Ascaris lumbricoides', 'genus' => 'Ascaris', 'family' => 'Ascarididae', 'order' => 'Ascaridida', 'class' => 'Chromadorea', 'phylum' => 'Nematoda'],
        ['name_scientific' => 'Toxocara canis', 'genus' => 'Toxocara', 'family' => 'Ascarididae', 'order' => 'Ascaridida', 'class' => 'Chromadorea', 'phylum' => 'Nematoda'],
        ['name_scientific' => 'Toxocara cati', 'genus' => 'Toxocara', 'family' => 'Ascarididae', 'order' => 'Ascaridida', 'class' => 'Chromadorea', 'phylum' => 'Nematoda'],
        ['name_scientific' => 'Ancylostoma duodenale', 'genus' => 'Ancylostoma', 'family' => 'Ancylostomatidae', 'order' => 'Strongylida', 'class' => 'Chromadorea', 'phylum' => 'Nematoda'],
        ['name_scientific' => 'Necator americanus', 'genus' => 'Necator', 'family' => 'Ancylostomatidae', 'order' => 'Strongylida', 'class' => 'Chromadorea', 'phylum' => 'Nematoda'],
        ['name_scientific' => 'Trichuris trichiura', 'genus' => 'Trichuris', 'family' => 'Trichuridae', 'order' => 'Trichurida', 'class' => 'Chromadorea', 'phylum' => 'Nematoda'],
        ['name_scientific' => 'Enterobius vermicularis', 'genus' => 'Enterobius', 'family' => 'Oxyuridae', 'order' => 'Oxyurida', 'class' => 'Chromadorea', 'phylum' => 'Nematoda'],
        ['name_scientific' => 'Strongyloides stercoralis', 'genus' => 'Strongyloides', 'family' => 'Strongyloididae', 'order' => 'Rhabditida', 'class' => 'Chromadorea', 'phylum' => 'Nematoda'],
        ['name_scientific' => 'Wuchereria bancrofti', 'genus' => 'Wuchereria', 'family' => 'Onchocercidae', 'order' => 'Spirurida', 'class' => 'Chromadorea', 'phylum' => 'Nematoda'],
        ['name_scientific' => 'Brugia malayi', 'genus' => 'Brugia', 'family' => 'Onchocercidae', 'order' => 'Spirurida', 'class' => 'Chromadorea', 'phylum' => 'Nematoda'],
        ['name_scientific' => 'Onchocerca volvulus', 'genus' => 'Onchocerca', 'family' => 'Onchocercidae', 'order' => 'Spirurida', 'class' => 'Chromadorea', 'phylum' => 'Nematoda'],
        ['name_scientific' => 'Loa loa', 'genus' => 'Loa', 'family' => 'Onchocercidae', 'order' => 'Spirurida', 'class' => 'Chromadorea', 'phylum' => 'Nematoda'],
        ['name_scientific' => 'Dracunculus medinensis', 'genus' => 'Dracunculus', 'family' => 'Dracunculidae', 'order' => 'Camallanida', 'class' => 'Chromadorea', 'phylum' => 'Nematoda'],
        ['name_scientific' => 'Trichinella spiralis', 'genus' => 'Trichinella', 'family' => 'Trichinellidae', 'order' => 'Trichurida', 'class' => 'Chromadorea', 'phylum' => 'Nematoda'],

        // Tapeworms (Cestoda)
        ['name_scientific' => 'Taenia solium', 'genus' => 'Taenia', 'family' => 'Taeniidae', 'order' => 'Cyclophyllidea', 'class' => 'Cestoda', 'phylum' => 'Platyhelminthes'],
        ['name_scientific' => 'Taenia saginata', 'genus' => 'Taenia', 'family' => 'Taeniidae', 'order' => 'Cyclophyllidea', 'class' => 'Cestoda', 'phylum' => 'Platyhelminthes'],
        ['name_scientific' => 'Echinococcus granulosus', 'genus' => 'Echinococcus', 'family' => 'Taeniidae', 'order' => 'Cyclophyllidea', 'class' => 'Cestoda', 'phylum' => 'Platyhelminthes'],
        ['name_scientific' => 'Echinococcus multilocularis', 'genus' => 'Echinococcus', 'family' => 'Taeniidae', 'order' => 'Cyclophyllidea', 'class' => 'Cestoda', 'phylum' => 'Platyhelminthes'],
        ['name_scientific' => 'Diphyllobothrium latum', 'genus' => 'Diphyllobothrium', 'family' => 'Diphyllobothriidae', 'order' => 'Diphyllobothriidea', 'class' => 'Cestoda', 'phylum' => 'Platyhelminthes'],
        ['name_scientific' => 'Hymenolepis nana', 'genus' => 'Hymenolepis', 'family' => 'Hymenolepididae', 'order' => 'Cyclophyllidea', 'class' => 'Cestoda', 'phylum' => 'Platyhelminthes'],
        ['name_scientific' => 'Dipylidium caninum', 'genus' => 'Dipylidium', 'family' => 'Dipylidiidae', 'order' => 'Cyclophyllidea', 'class' => 'Cestoda', 'phylum' => 'Platyhelminthes'],

        // Flukes (Trematoda)
        ['name_scientific' => 'Schistosoma mansoni', 'genus' => 'Schistosoma', 'family' => 'Schistosomatidae', 'order' => 'Strigeidida', 'class' => 'Trematoda', 'phylum' => 'Platyhelminthes'],
        ['name_scientific' => 'Schistosoma haematobium', 'genus' => 'Schistosoma', 'family' => 'Schistosomatidae', 'order' => 'Strigeidida', 'class' => 'Trematoda', 'phylum' => 'Platyhelminthes'],
        ['name_scientific' => 'Schistosoma japonicum', 'genus' => 'Schistosoma', 'family' => 'Schistosomatidae', 'order' => 'Strigeidida', 'class' => 'Trematoda', 'phylum' => 'Platyhelminthes'],
        ['name_scientific' => 'Fasciola hepatica', 'genus' => 'Fasciola', 'family' => 'Fasciolidae', 'order' => 'Echinostomida', 'class' => 'Trematoda', 'phylum' => 'Platyhelminthes'],
        ['name_scientific' => 'Fasciolopsis buski', 'genus' => 'Fasciolopsis', 'family' => 'Fasciolidae', 'order' => 'Echinostomida', 'class' => 'Trematoda', 'phylum' => 'Platyhelminthes'],
        ['name_scientific' => 'Clonorchis sinensis', 'genus' => 'Clonorchis', 'family' => 'Opisthorchiidae', 'order' => 'Opisthorchiida', 'class' => 'Trematoda', 'phylum' => 'Platyhelminthes'],
        ['name_scientific' => 'Opisthorchis viverrini', 'genus' => 'Opisthorchis', 'family' => 'Opisthorchiidae', 'order' => 'Opisthorchiida', 'class' => 'Trematoda', 'phylum' => 'Platyhelminthes'],
        ['name_scientific' => 'Paragonimus westermani', 'genus' => 'Paragonimus', 'family' => 'Paragonimidae', 'order' => 'Plagiorchiida', 'class' => 'Trematoda', 'phylum' => 'Platyhelminthes'],

        // Protozoa
        ['name_scientific' => 'Plasmodium falciparum', 'genus' => 'Plasmodium', 'family' => 'Plasmodiidae', 'order' => 'Haemosporida', 'class' => 'Aconoidasida', 'phylum' => 'Apicomplexa'],
        ['name_scientific' => 'Plasmodium vivax', 'genus' => 'Plasmodium', 'family' => 'Plasmodiidae', 'order' => 'Haemosporida', 'class' => 'Aconoidasida', 'phylum' => 'Apicomplexa'],
        ['name_scientific' => 'Plasmodium malariae', 'genus' => 'Plasmodium', 'family' => 'Plasmodiidae', 'order' => 'Haemosporida', 'class' => 'Aconoidasida', 'phylum' => 'Apicomplexa'],
        ['name_scientific' => 'Plasmodium ovale', 'genus' => 'Plasmodium', 'family' => 'Plasmodiidae', 'order' => 'Haemosporida', 'class' => 'Aconoidasida', 'phylum' => 'Apicomplexa'],
        ['name_scientific' => 'Trypanosoma brucei', 'genus' => 'Trypanosoma', 'family' => 'Trypanosomatidae', 'order' => 'Trypanosomatida', 'class' => 'Kinetoplastea', 'phylum' => 'Euglenozoa'],
        ['name_scientific' => 'Trypanosoma cruzi', 'genus' => 'Trypanosoma', 'family' => 'Trypanosomatidae', 'order' => 'Trypanosomatida', 'class' => 'Kinetoplastea', 'phylum' => 'Euglenozoa'],
        ['name_scientific' => 'Leishmania donovani', 'genus' => 'Leishmania', 'family' => 'Trypanosomatidae', 'order' => 'Trypanosomatida', 'class' => 'Kinetoplastea', 'phylum' => 'Euglenozoa'],
        ['name_scientific' => 'Leishmania major', 'genus' => 'Leishmania', 'family' => 'Trypanosomatidae', 'order' => 'Trypanosomatida', 'class' => 'Kinetoplastea', 'phylum' => 'Euglenozoa'],
        ['name_scientific' => 'Giardia lamblia', 'genus' => 'Giardia', 'family' => 'Hexamitidae', 'order' => 'Diplomonadida', 'class' => 'Trepomonadea', 'phylum' => 'Metamonada'],
        ['name_scientific' => 'Entamoeba histolytica', 'genus' => 'Entamoeba', 'family' => 'Entamoebidae', 'order' => 'Amoebida', 'class' => 'Archamoebae', 'phylum' => 'Amoebozoa'],
        ['name_scientific' => 'Cryptosporidium parvum', 'genus' => 'Cryptosporidium', 'family' => 'Cryptosporidiidae', 'order' => 'Eucoccidiorida', 'class' => 'Conoidasida', 'phylum' => 'Apicomplexa'],
        ['name_scientific' => 'Toxoplasma gondii', 'genus' => 'Toxoplasma', 'family' => 'Sarcocystidae', 'order' => 'Eucoccidiorida', 'class' => 'Conoidasida', 'phylum' => 'Apicomplexa'],
        ['name_scientific' => 'Babesia microti', 'genus' => 'Babesia', 'family' => 'Babesiidae', 'order' => 'Piroplasmida', 'class' => 'Aconoidasida', 'phylum' => 'Apicomplexa'],
        ['name_scientific' => 'Trichomonas vaginalis', 'genus' => 'Trichomonas', 'family' => 'Trichomonadidae', 'order' => 'Trichomonadida', 'class' => 'Parabasalia', 'phylum' => 'Metamonada'],

        // Additional ticks
        ['name_scientific' => 'Ixodes pacificus', 'genus' => 'Ixodes', 'family' => 'Ixodidae', 'order' => 'Ixodida', 'class' => 'Arachnida', 'phylum' => 'Arthropoda'],
        ['name_scientific' => 'Ixodes persulcatus', 'genus' => 'Ixodes', 'family' => 'Ixodidae', 'order' => 'Ixodida', 'class' => 'Arachnida', 'phylum' => 'Arthropoda'],
        ['name_scientific' => 'Amblyomma americanum', 'genus' => 'Amblyomma', 'family' => 'Ixodidae', 'order' => 'Ixodida', 'class' => 'Arachnida', 'phylum' => 'Arthropoda'],
        ['name_scientific' => 'Amblyomma maculatum', 'genus' => 'Amblyomma', 'family' => 'Ixodidae', 'order' => 'Ixodida', 'class' => 'Arachnida', 'phylum' => 'Arthropoda'],
        ['name_scientific' => 'Rhipicephalus appendiculatus', 'genus' => 'Rhipicephalus', 'family' => 'Ixodidae', 'order' => 'Ixodida', 'class' => 'Arachnida', 'phylum' => 'Arthropoda'],
        ['name_scientific' => 'Rhipicephalus evertsi', 'genus' => 'Rhipicephalus', 'family' => 'Ixodidae', 'order' => 'Ixodida', 'class' => 'Arachnida', 'phylum' => 'Arthropoda'],
        ['name_scientific' => 'Dermacentor variabilis', 'genus' => 'Dermacentor', 'family' => 'Ixodidae', 'order' => 'Ixodida', 'class' => 'Arachnida', 'phylum' => 'Arthropoda'],
        ['name_scientific' => 'Dermacentor andersoni', 'genus' => 'Dermacentor', 'family' => 'Ixodidae', 'order' => 'Ixodida', 'class' => 'Arachnida', 'phylum' => 'Arthropoda'],
        ['name_scientific' => 'Hyalomma anatolicum', 'genus' => 'Hyalomma', 'family' => 'Ixodidae', 'order' => 'Ixodida', 'class' => 'Arachnida', 'phylum' => 'Arthropoda'],
        ['name_scientific' => 'Haemaphysalis longicornis', 'genus' => 'Haemaphysalis', 'family' => 'Ixodidae', 'order' => 'Ixodida', 'class' => 'Arachnida', 'phylum' => 'Arthropoda'],

        // Additional nematodes
        ['name_scientific' => 'Ancylostoma braziliense', 'genus' => 'Ancylostoma', 'family' => 'Ancylostomatidae', 'order' => 'Strongylida', 'class' => 'Chromadorea', 'phylum' => 'Nematoda'],
        ['name_scientific' => 'Ancylostoma caninum', 'genus' => 'Ancylostoma', 'family' => 'Ancylostomatidae', 'order' => 'Strongylida', 'class' => 'Chromadorea', 'phylum' => 'Nematoda'],
        ['name_scientific' => 'Uncinaria stenocephala', 'genus' => 'Uncinaria', 'family' => 'Ancylostomatidae', 'order' => 'Strongylida', 'class' => 'Chromadorea', 'phylum' => 'Nematoda'],
        ['name_scientific' => 'Trichuris vulpis', 'genus' => 'Trichuris', 'family' => 'Trichuridae', 'order' => 'Trichurida', 'class' => 'Chromadorea', 'phylum' => 'Nematoda'],
        ['name_scientific' => 'Strongyloides ratti', 'genus' => 'Strongyloides', 'family' => 'Strongyloididae', 'order' => 'Rhabditida', 'class' => 'Chromadorea', 'phylum' => 'Nematoda'],
        ['name_scientific' => 'Dirofilaria immitis', 'genus' => 'Dirofilaria', 'family' => 'Onchocercidae', 'order' => 'Spirurida', 'class' => 'Chromadorea', 'phylum' => 'Nematoda'],
        ['name_scientific' => 'Dirofilaria repens', 'genus' => 'Dirofilaria', 'family' => 'Onchocercidae', 'order' => 'Spirurida', 'class' => 'Chromadorea', 'phylum' => 'Nematoda'],
        ['name_scientific' => 'Brugia timori', 'genus' => 'Brugia', 'family' => 'Onchocercidae', 'order' => 'Spirurida', 'class' => 'Chromadorea', 'phylum' => 'Nematoda'],
        ['name_scientific' => 'Mansonella perstans', 'genus' => 'Mansonella', 'family' => 'Onchocercidae', 'order' => 'Spirurida', 'class' => 'Chromadorea', 'phylum' => 'Nematoda'],
        ['name_scientific' => 'Mansonella ozzardi', 'genus' => 'Mansonella', 'family' => 'Onchocercidae', 'order' => 'Spirurida', 'class' => 'Chromadorea', 'phylum' => 'Nematoda'],

        // Additional cestodes
        ['name_scientific' => 'Taenia asiatica', 'genus' => 'Taenia', 'family' => 'Taeniidae', 'order' => 'Cyclophyllidea', 'class' => 'Cestoda', 'phylum' => 'Platyhelminthes'],
        ['name_scientific' => 'Taenia multiceps', 'genus' => 'Taenia', 'family' => 'Taeniidae', 'order' => 'Cyclophyllidea', 'class' => 'Cestoda', 'phylum' => 'Platyhelminthes'],
        ['name_scientific' => 'Echinococcus vogeli', 'genus' => 'Echinococcus', 'family' => 'Taeniidae', 'order' => 'Cyclophyllidea', 'class' => 'Cestoda', 'phylum' => 'Platyhelminthes'],
        ['name_scientific' => 'Diphyllobothrium dendriticum', 'genus' => 'Diphyllobothrium', 'family' => 'Diphyllobothriidae', 'order' => 'Diphyllobothriidea', 'class' => 'Cestoda', 'phylum' => 'Platyhelminthes'],
        ['name_scientific' => 'Hymenolepis diminuta', 'genus' => 'Hymenolepis', 'family' => 'Hymenolepididae', 'order' => 'Cyclophyllidea', 'class' => 'Cestoda', 'phylum' => 'Platyhelminthes'],
        ['name_scientific' => 'Mesocestoides lineatus', 'genus' => 'Mesocestoides', 'family' => 'Mesocestoididae', 'order' => 'Cyclophyllidea', 'class' => 'Cestoda', 'phylum' => 'Platyhelminthes'],

        // Additional trematodes
        ['name_scientific' => 'Schistosoma intercalatum', 'genus' => 'Schistosoma', 'family' => 'Schistosomatidae', 'order' => 'Strigeidida', 'class' => 'Trematoda', 'phylum' => 'Platyhelminthes'],
        ['name_scientific' => 'Schistosoma mekongi', 'genus' => 'Schistosoma', 'family' => 'Schistosomatidae', 'order' => 'Strigeidida', 'class' => 'Trematoda', 'phylum' => 'Platyhelminthes'],
        ['name_scientific' => 'Fasciola gigantica', 'genus' => 'Fasciola', 'family' => 'Fasciolidae', 'order' => 'Echinostomida', 'class' => 'Trematoda', 'phylum' => 'Platyhelminthes'],
        ['name_scientific' => 'Opisthorchis felineus', 'genus' => 'Opisthorchis', 'family' => 'Opisthorchiidae', 'order' => 'Opisthorchiida', 'class' => 'Trematoda', 'phylum' => 'Platyhelminthes'],
        ['name_scientific' => 'Paragonimus heterotremus', 'genus' => 'Paragonimus', 'family' => 'Paragonimidae', 'order' => 'Plagiorchiida', 'class' => 'Trematoda', 'phylum' => 'Platyhelminthes'],
        ['name_scientific' => 'Echinostoma revolutum', 'genus' => 'Echinostoma', 'family' => 'Echinostomatidae', 'order' => 'Echinostomida', 'class' => 'Trematoda', 'phylum' => 'Platyhelminthes'],

        // Additional protozoa
        ['name_scientific' => 'Plasmodium knowlesi', 'genus' => 'Plasmodium', 'family' => 'Plasmodiidae', 'order' => 'Haemosporida', 'class' => 'Aconoidasida', 'phylum' => 'Apicomplexa'],
        ['name_scientific' => 'Trypanosoma evansi', 'genus' => 'Trypanosoma', 'family' => 'Trypanosomatidae', 'order' => 'Trypanosomatida', 'class' => 'Kinetoplastea', 'phylum' => 'Euglenozoa'],
        ['name_scientific' => 'Trypanosoma equiperdum', 'genus' => 'Trypanosoma', 'family' => 'Trypanosomatidae', 'order' => 'Trypanosomatida', 'class' => 'Kinetoplastea', 'phylum' => 'Euglenozoa'],
        ['name_scientific' => 'Leishmania infantum', 'genus' => 'Leishmania', 'family' => 'Trypanosomatidae', 'order' => 'Trypanosomatida', 'class' => 'Kinetoplastea', 'phylum' => 'Euglenozoa'],
        ['name_scientific' => 'Leishmania braziliensis', 'genus' => 'Leishmania', 'family' => 'Trypanosomatidae', 'order' => 'Trypanosomatida', 'class' => 'Kinetoplastea', 'phylum' => 'Euglenozoa'],
        ['name_scientific' => 'Giardia duodenalis', 'genus' => 'Giardia', 'family' => 'Hexamitidae', 'order' => 'Diplomonadida', 'class' => 'Trepomonadea', 'phylum' => 'Metamonada'],
        ['name_scientific' => 'Entamoeba dispar', 'genus' => 'Entamoeba', 'family' => 'Entamoebidae', 'order' => 'Amoebida', 'class' => 'Archamoebae', 'phylum' => 'Amoebozoa'],
        ['name_scientific' => 'Cryptosporidium hominis', 'genus' => 'Cryptosporidium', 'family' => 'Cryptosporidiidae', 'order' => 'Eucoccidiorida', 'class' => 'Conoidasida', 'phylum' => 'Apicomplexa'],
        ['name_scientific' => 'Babesia divergens', 'genus' => 'Babesia', 'family' => 'Babesiidae', 'order' => 'Piroplasmida', 'class' => 'Aconoidasida', 'phylum' => 'Apicomplexa'],
        ['name_scientific' => 'Trichomonas tenax', 'genus' => 'Trichomonas', 'family' => 'Trichomonadidae', 'order' => 'Trichomonadida', 'class' => 'Parabasalia', 'phylum' => 'Metamonada'],

        // Additional mites
        ['name_scientific' => 'Otodectes cynotis', 'genus' => 'Otodectes', 'family' => 'Psoroptidae', 'order' => 'Sarcoptiformes', 'class' => 'Arachnida', 'phylum' => 'Arthropoda'],
        ['name_scientific' => 'Cheyletiella yasguri', 'genus' => 'Cheyletiella', 'family' => 'Cheyletiellidae', 'order' => 'Trombidiformes', 'class' => 'Arachnida', 'phylum' => 'Arthropoda'],
        ['name_scientific' => 'Trombicula autumnalis', 'genus' => 'Trombicula', 'family' => 'Trombiculidae', 'order' => 'Trombidiformes', 'class' => 'Arachnida', 'phylum' => 'Arthropoda'],
        ['name_scientific' => 'Ornithonyssus bacoti', 'genus' => 'Ornithonyssus', 'family' => 'Macronyssidae', 'order' => 'Mesostigmata', 'class' => 'Arachnida', 'phylum' => 'Arthropoda'],
        ['name_scientific' => 'Dermanyssus gallinae', 'genus' => 'Dermanyssus', 'family' => 'Dermanyssidae', 'order' => 'Mesostigmata', 'class' => 'Arachnida', 'phylum' => 'Arthropoda'],

        // Additional ticks for variety
        ['name_scientific' => 'Ixodes holocyclus', 'genus' => 'Ixodes', 'family' => 'Ixodidae', 'order' => 'Ixodida', 'class' => 'Arachnida', 'phylum' => 'Arthropoda'],
        ['name_scientific' => 'Ixodes hexagonus', 'genus' => 'Ixodes', 'family' => 'Ixodidae', 'order' => 'Ixodida', 'class' => 'Arachnida', 'phylum' => 'Arthropoda'],
        ['name_scientific' => 'Amblyomma cajennense', 'genus' => 'Amblyomma', 'family' => 'Ixodidae', 'order' => 'Ixodida', 'class' => 'Arachnida', 'phylum' => 'Arthropoda'],
        ['name_scientific' => 'Amblyomma testudinarium', 'genus' => 'Amblyomma', 'family' => 'Ixodidae', 'order' => 'Ixodida', 'class' => 'Arachnida', 'phylum' => 'Arthropoda'],
        ['name_scientific' => 'Rhipicephalus bursa', 'genus' => 'Rhipicephalus', 'family' => 'Ixodidae', 'order' => 'Ixodida', 'class' => 'Arachnida', 'phylum' => 'Arthropoda'],
        ['name_scientific' => 'Rhipicephalus turanicus', 'genus' => 'Rhipicephalus', 'family' => 'Ixodidae', 'order' => 'Ixodida', 'class' => 'Arachnida', 'phylum' => 'Arthropoda'],
        ['name_scientific' => 'Dermacentor occidentalis', 'genus' => 'Dermacentor', 'family' => 'Ixodidae', 'order' => 'Ixodida', 'class' => 'Arachnida', 'phylum' => 'Arthropoda'],
        ['name_scientific' => 'Dermacentor albipictus', 'genus' => 'Dermacentor', 'family' => 'Ixodidae', 'order' => 'Ixodida', 'class' => 'Arachnida', 'phylum' => 'Arthropoda'],
        ['name_scientific' => 'Hyalomma dromedarii', 'genus' => 'Hyalomma', 'family' => 'Ixodidae', 'order' => 'Ixodida', 'class' => 'Arachnida', 'phylum' => 'Arthropoda'],
        ['name_scientific' => 'Haemaphysalis punctata', 'genus' => 'Haemaphysalis', 'family' => 'Ixodidae', 'order' => 'Ixodida', 'class' => 'Arachnida', 'phylum' => 'Arthropoda'],
    ];

    public function definition(): array
    {
        $species = $this->faker->unique()->randomElement($this->speciesData);

        return [
            'name_scientific' => $species['name_scientific'],
            'genus' => $species['genus'],
            'family' => $species['family'],
            'order' => $species['order'],
            'class' => $species['class'],
            'phylum' => $species['phylum'],
        ];
    }
}
