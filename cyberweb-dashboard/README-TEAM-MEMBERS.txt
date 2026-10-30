CYBERWEB - TEAM MEMBER FOLDERS + SAVED TEMPLATE DATA (HINGLISH)

NEW FLOW
1. My Projects -> New Project par click karo.
2. Project Name aur Hotel/Property Name fill karo.
3. Purane Project Location ki jagah Team Member dropdown se ek naam choose karo.
   Options: Shravani, Priti Patel, Sushma, Sapna, Kamini, Rahul Uke, Gaurav,
   Satish, Rajesh, Jitesh, daksha, ananaya.
4. Hotel Details page mein hotel info, amenities aur gallery photos Save karo.
5. Templates page par Harbor / Willow / Urban / Minimal mein se design choose karo.
6. Preview popup mein Brand Name aur Main Headline customize karo.
   "Save Text to MySQL" button par click karne se us template ki customization DB mein save hoti hai.
   "Use Template" -> "Apply Template" karne par selected design + customization save hoti hai.
7. My Projects mein Team Member ke folder par click karo. Us member ke projects
   aur unka selected template wahi dikhega.

WHAT GETS SAVED IN MYSQL
- projects.team_member: selected team member
- projects.template: project's selected final design (Harbor/Willow/Urban/Minimal)
- template_settings: per-project, per-design Brand Name / Main Headline overrides
- hotel_details: hotel name, phone, address, amenities, things-to-do
- gallery_images: saved gallery image records; image files stay under storage/gallery

UPGRADING AN EXISTING XAMPP DATABASE
- Existing project records safe hain. Purana location column delete/drop NAHI hota.
- Dashboard automatically ALTER TABLE projects ADD team_member and CREATE TABLE template_settings
  attempt karega (requires MySQL ALTER and CREATE permissions).
- Agar permissions ki wajah se error aaye, phpMyAdmin -> cyberweb -> SQL mein
  database/team_members_upgrade.sql run karo. ALTER statement sirf tab chalana
  jab projects.team_member column abhi exist nahi karta.
- Purane projects jo pehle create hue the woh "Unassigned" folder mein dikhenge.
  "Edit project" -> Team Member select karke Save karo to proper folder mein move honge.
- Apne live storage/gallery ko update ZIP extract karte waqt delete mat karna.
- Existing MySQL database ka backup separately lena zaroori hai.

IMPORTANT NOTES
- Ye folders DASHBOARD KE ANDAR database-backed project groups hain, disk ke actual
  generated website folders ya separately published sites nahi.
- Team Member field currently organizational label hai, login/permissions nahi.
  Authentication ab bhi signed-in dashboard user_id se control hoti hai.
- Selected template ki custom text final private preview mein reflect hoti hai.
- Template reset + Save to MySQL karne par hotel brand/default text follow hoga.
- PHP/MySQL local execution XAMPP par verify karna hai; project ZIP mein customer
  gallery photos ya live database contents include nahi hote.
