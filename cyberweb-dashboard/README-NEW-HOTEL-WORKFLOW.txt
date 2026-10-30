CYBERWEB – UPDATED 3-STEP HOTEL WEBSITE WORKFLOW (HINGLISH)

AB USER FLOW
1. Login -> My Projects -> New Project.
2. Project Name / Hotel Property Name / Team Member dropdown save karein.
3. Automatically 'Hotel Details' page khulega (hotel-details.php).
4. Hotel Name, Phone No, Address, Amenities (one per line), Things To Do (one per line) fill karein.
   Neeche FAQs section mein FAQ 1, FAQ 2 aur FAQ 3 ke Question + Answer optional fill karein.
   Har project ke liye maximum 3 FAQs hain; blank pairs website par nahi dikhenge.
5. Photo Gallery mein ek saath 10–12 JPG/PNG/WebP photos SELECT kar sakti hain, YA photo ZIP attach kar sakti hain.
   Dono ek saath bhi attach kar sakti hain. Direct images ka thumbnail Save se pehle dikh jayega.
   ZIP ke andar ki photos Save ke baad dikhengi.
6. Save & Continue to Templates -> 4 design options (Harbor, Willow, Urban, Minimal) khulenge.
7. Design preview karein, Use Template -> Apply Template click karein.
8. Selected design ki final website preview khulegi (preview-site.php).
9. 'Edit Hotel Details' se content/photos/FAQs update kar sakte hain; 'Change Design' se template badal sakte hain.
10. My Projects mein selected Team Member ke expandable folder mein project aur final template dikhega.
11. Templates -> Preview mein Brand Name/Headline text Save to MySQL se save hota hai,
    aur Apply Template par bhi selected design + customization database mein persist hoti hai.

IMPORTANT FEATURES
- Photos, amenities, things-to-do aur 3 optional FAQs ab MySQL mein project se linked hain (images storage/gallery mein).
- FAQs 'hotel_faqs' MySQL table mein save hote hain. Harbor, Willow, Urban aur Minimal
  sabki final website preview mein FAQ questions click karke answers khol sakte hain.
- FAQs per project saved hain; template change karne par nahi mitenge. Blank Question aur Answer
  dono chhodo to us FAQ ko remove kiya ja sakta hai; sirf ek field fill karne par error aayega.
- Team Member dropdown aur database-backed folder groupings ka detail README-TEAM-MEMBERS.txt mein hai.
- Preview selected template ke colors/layout mein saved hotel info dikhata hai.
- Har template concept card mein saved gallery ka photo background aur thumbnail strip dikhte hain.
- Template Preview dialog mein uploaded gallery ki thumbnails aur final website preview mein full gallery dikhti hai.
- Broken photo file references par clear warning dikhegi (silent blank gallery nahi).
- Ye PRIVATE, login-protected preview hai. Isse live domain par publish nahi kiya jata.
- User template change kare to hotel details aur uploaded photos safe rahengi.
- Gallery direct JPG/PNG/WebP multiple-file upload ya ZIP dono se photos import karti hai.
- Direct file selection par thumbnail preview immediately dikhega; ZIP ka original folder structure extract nahi hota.
- Dobara "Select Photos" click karne par next JPG/PNG files pehle selected files mein
  add hoti rahengi (maximum 12 per Save); har thumbnail par Remove option bhi hai.
- Save ke baad website confirmation batayega ki kitni new gallery photos saved hui.
- JPG/PNG direct upload PHP ZIP extension ke bina bhi chalna chahiye. ZIP importer tabhi chalega
  jab ZIP input mein actual .zip file select hui ho. Galat JPG/PNG ZIP field mein attach karne
  par useful error message aayega.
- Agar ek hi save mein images aur ZIP attach hain lekin PHP ZIP missing hai, direct photos save
  hongi aur optional ZIP skip hogi; website preview clear notice dikhayega.
- ZIP max 20 MB; 12 direct photos per selection; each image max 8 MB; max 30 photos per project.
- Editor mein old photos ko checkbox se remove bhi kar sakte hain.

XAMPP INSTALLATION
1. Existing C:\xampp\htdocs\cyberweb-dashboard ka backup lo.
2. Updated ZIP extract kar ke contents ko C:\xampp\htdocs\cyberweb-dashboard mein replace/merge karo.
   Double nesting mat karna (cyberweb-dashboard\cyberweb-dashboard galat hai).
3. XAMPP Apache aur MySQL start karo.
4. Database ka naam 'cyberweb' hai (settings api/db.php mein).
5. 'cyberweb.users' table pehle se honi chahiye, aur 'cyberweb.projects' table database/projects.sql se.
6. Naye hotel_details, hotel_faqs aur gallery_images tables first Hotel Details visit par automatically create hote hain
   (agar DB user ko CREATE permission hai). Agar create na ho, phpMyAdmin -> cyberweb -> SQL mein
   database/hotel_details.sql run karo.
7. PHP ZIP extension enabled honi chahiye. XAMPP php.ini mein extension=zip enable karo if needed.
8. 10–12 photos upload karne ke liye php.ini mein upload_max_filesize=25M,
   post_max_size=128M, max_file_uploads=20, memory_limit=256M set karein, then Apache restart.
9. Login -> My Projects -> Create project -> Hotel Details -> Select Template -> Preview website.
10. Browser Ctrl+Shift+R ek baar press karein so updated CSS/JS load ho jaye.

SECURITY / BEHAVIOR
- Saved gallery files are stored under storage/gallery, protected by storage/.htaccess on Apache.
- Photos are served through gallery-image.php which requires authentication and checks project ownership.
- Image endpoint ab PHP include output ko buffer/clear karta hai, binary photo bytes hi return
  karta hai, session lock streaming se pehle release karta hai aur stale image cache avoid karta hai.
  Agar photo file valid hai to is change ke liye re-upload karna zaroori nahi hona chahiye.
- The ZIP handler accepts only validated JPG/PNG/WebP images and ignores original ZIP paths.
- Keep storage/gallery data when updating site files; don't delete the storage/gallery folder with customer images.
- IMPORTANT: Database mein gallery_images records hone se actual photos restore nahi hoti. Photos ki files
  C:\xampp\htdocs\cyberweb-dashboard\storage\gallery\PROJECT_ID\ ke andar hoti hain.
  Agar folder delete hua hai, old backup se restore karo, ya Hotel Details se missing records remove karke re-upload karo.
- MySQL database backup alag se maintain karein; ZIP mein your live data include nahi hota.
- This demo is not a complete public website publishing/deployment service.

TROUBLESHOOTING
- Database connection failed: MySQL START karo, api/db.php credentials aur 'cyberweb' DB check karo.
- Hotel details tables not ready: database/hotel_details.sql import karo.
- Photos selected hain but nahi dikh rahi: browser Ctrl+Shift+R, images save karo,
  Hotel Details par "Uploaded Photos" mein image actual saved hai ya nahi check karo.
  Gallery count 0 = files upload nahi hui; missing file warning = storage/gallery lost ya unreadable.
  Agar photos saved hain but browser load failed = gallery-image.php endpoint response, PHP/XAMPP logs aur login check karo.
  Gallery section mein ab browser request HTTP status ke saath diagnostic notice aayega.
  Agar HTTP 200 image/* aa raha hai lekin photo decode nahi ho rahi, warning ab actual
  image signature inspect karke batayegi ki PHP output extra bytes hain ya file corrupt ho sakti hai.
  storage/gallery folder delete ya replace mat karna.
- ZIP upload failed: check extension=zip, upload_max_filesize and post_max_size in php.ini.
- Photo storage permission issue: Apache ko storage/gallery mein write permission do.
- Projects page sidebar mein My Projects item active hota hai (Overview nahi).
- Only a private preview is generated; website publishing/ZIP export is not part of this version.
